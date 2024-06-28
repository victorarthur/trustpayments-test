<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrustPaymentsController extends Controller
{
    private string $secret = '60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c';

    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    private function createJwtSignature(string $header, string $payload): string
    {
        $encodedHeader = $this->base64UrlEncode($header);
        $encodedPayload = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $this->secret, true);
        return $this->base64UrlEncode($signature);
    }

    public function generateJWT(): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode(json_decode(request()->getContent(), true));
        $signature = $this->createJwtSignature($header, $payload);

        return $this->base64UrlEncode($header) . "." . $this->base64UrlEncode($payload) . "." . $signature;
    }

    private function validateJWT(string $jwt): array
    {
        [$header, $payload, $signature] = explode('.', $jwt);
        $decodedHeader = $this->base64UrlDecode($header);
        $decodedPayload = $this->base64UrlDecode($payload);
        $expectedSignature = $this->createJwtSignature($decodedHeader, $decodedPayload);

        return [
            'valid' => $expectedSignature === $signature,
            'payload' => json_decode($decodedPayload, true),
        ];
    }

    private function retrieveFailure(string $error_code, string $error_data = ''): string
    {
        $error_messages = [
            '30000' => "The transaction failed due to invalid transaction information.",
            '60010' => "The transaction failed due to a communication error. Please verify whether the amount has been charged. If necessary, contact our customer service for assistance with a refund.",
            '60034' => "The transaction failed due to a communication error. Please verify whether the amount has been charged. If necessary, contact our customer service for assistance with a refund.",
            '99999' => "The transaction failed due to a communication error. Please verify whether the amount has been charged. If necessary, contact our customer service for assistance with a refund.",
            '60022' => "The transaction failed due to authorization code is not provided. Please attempt the transaction again, ensuring the authorization code is entered correctly, or use an alternative payment method.",
            '70000' => "The transaction failed due authorization is attempted but the authorization code is invalid. Please attempt the transaction again, ensuring the authorization code is entered correctly, or use an alternative payment method.",
            '71000' => "The card issuer declined the request due to absence of Strong Customer Authentication (SCA). You will need to resubmit the transaction with the necessary EMV 3DS authentication.",
        ];

        $failure_reason = $error_messages[$error_code] ?? 'Unknown error code: ' . $error_code;

        if ($error_code === '30000' && $error_data) {
            $failure_reason .= ' Possible error in the ' . $error_data . ' field.';
        }

        return $failure_reason;
    }

    private function handleInvalidJwt(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'JWT validation failed.',
        ]);
    }

    private function handleSuccessfulPayment(array $response, array $payload): JsonResponse
    {
        return response()->json([
            'success' => true,
            'transactionId' => $response['transactionreference'],
            'payload' => $payload,
        ]);
    }

    private function handleFailedPayment(array $response, string $failure_reason): JsonResponse
    {
        $sum_message = $response['errormessage'] . ($failure_reason ? '. Failure reason: ' . $failure_reason : '');

        return response()->json([
            'success' => false,
            'message' => $sum_message,
        ]);
    }

    public function processAuth(): JsonResponse
    {
        try {
            $response = request()->all();

            $is_jwt_valid = $this->validateJWT($response['jwt']);

            if (!$is_jwt_valid['valid']) {
                return $this->handleInvalidJwt();
            }

            $payload = $is_jwt_valid['payload']['payload'];
            // This is for only actual payment processing. Not for subscriptions or recurring payments.
            $auth_result = collect($payload['response'])->firstWhere('requesttypedescription', 'AUTH');

            if ($auth_result) {
                if ($auth_result['errorcode'] === '0') {
                    return $this->handleSuccessfulPayment($auth_result, $payload);
                }

                $failure_reason = $this->retrieveFailure(
                    $auth_result['errorcode'],
                    $auth_result['errordata'] ?? ''
                );
            } else {
                $failure_reason = 'No valid authorization found in the response.';
            }
            //$payload['response'] is an array get the last element of the array
            $last_response = end($payload['response']);
            return $this->handleFailedPayment($last_response, $failure_reason);
        } catch (\Exception $e) {
            Log::error('An error occurred while processing the authentication: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
