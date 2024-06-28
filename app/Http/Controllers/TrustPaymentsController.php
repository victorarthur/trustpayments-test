<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrustPaymentsController extends Controller
{
    private function base64UrlEncode($data)
    {
        // Base64 encode the data
        $base64 = base64_encode($data);
        // Replace '+' with '-', '/' with '_', and remove '='
        return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
    }

    private function createJwtSignature($header, $payload, $secret)
    {
        // Encode the header and payload
        $encodedHeader = $this->base64UrlEncode($header);
        $encodedPayload = $this->base64UrlEncode($payload);

        // Create the HMACSHA256 signature
        $signature = hash_hmac('sha256', $encodedHeader . "." . $encodedPayload, $secret, true);

        // Encode the signature using base64UrlEncode
        return $this->base64UrlEncode($signature);
    }

    public function generateJWT()
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode(json_decode(request()->getContent(), true));
        $secret = '60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c';
        $signature = $this->createJwtSignature($header, $payload, $secret); // returns with base64UrlEncoded signature

        return $this->base64UrlEncode($header) . "." . $this->base64UrlEncode($payload) . "." . $signature;
    }

    private function validateJWT(string $jwt): bool
    {
        $parts = explode('.', $jwt);
        $header = json_decode(base64_decode($parts[0]), true);
        $payload = json_decode(base64_decode($parts[1]), true);
        $signature = $parts[2];

        // Check if the signature is correct
        $expectedSignature = $this->createJwtSignature($parts[0], $parts[1], '60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c');

       // Check if expected signature is equal to the signature in the JWT

        if ($expectedSignature === $signature) {
            return true;
        }
        
        return false;
    }


    public function processAuth()
    {
        $response = request()->all();

        // Check if the response is valid
        // Validate JWT in the response that the signature is correct
        $is_jwt_valid = $this->validateJWT($response['jwt']);

        return ($is_jwt_valid && $response['errorcode'] == 0) ? response()->json([
            'success' => true,
            'transactionId' => $response['transactionreference'],

        ]) : response()->json([
            'success' => false,
            'message' => $response['errormessage'],
        ]);

    }
}
