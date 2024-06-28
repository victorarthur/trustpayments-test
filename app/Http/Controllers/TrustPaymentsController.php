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

    private function base64UrlDecode($data)
    {
        // Replace '-' with '+', '_' with '/', and add '=' if needed
        $base64 = str_replace(['-', '_'], ['+', '/'], $data);
        // Base64 decode the data
        return base64_decode($base64);
    }

    public function generateJWT()
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode(json_decode(request()->getContent(), true));
        $secret = '60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c';
        $signature = $this->createJwtSignature($header, $payload, $secret); // returns with base64UrlEncoded signature

        return $this->base64UrlEncode($header) . "." . $this->base64UrlEncode($payload) . "." . $signature;
    }

    private function validateJWT(string $jwt): array
    {
        $parts = explode('.', $jwt);
        $header = $this->base64UrlDecode($parts[0]);
        $payload = $this->base64UrlDecode($parts[1]);
        $signature = $parts[2];

        // Check if the signature is correct
        $expectedSignature = $this->createJwtSignature($header, $payload, '60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c');

        // Check if expected signature is equal to the signature in the JWT
        if ($expectedSignature === $signature) {
            return [
                'valid' => true,
                'payload' => json_decode($payload, true),
                ];
        }

        return ['valid' => false];
    }

    private function retrieveFailiure(String $error_code, String $error_data = '',) {
        if($auth_result['errorcode'] == '30000') {
            // Implementation error: Indicates invalid data has been submitted within the payload of the JWT
            $failiure_reason = "The transaction failed due to invalid transaction information.";
            if($error_data) {
                $failiure_reason = $failiure_reason . ' Possible error in the ' . $error_data . ' field.';
            }
            return $failiure_reason;
        }

        if(in_array($auth_result['errorcode'], ['60010', '60034', '99999'])) {
            //This can be due to a communication problem with a bank or third party.
            // We recommend informing the customer of the problem and to contact you to query the issue. You will need to contact our Support Team, providing a copy of the entire request submitted and response returned, and we will contact the relevant parties to establish the status of the request. In the interest of security, ensure you omit or mask sensitive field values, such as card details.
            return "The transaction failed due to a communication error. Please verify whether the amount has been charged. If necessary, contact our customer service for assistance with a refund.";
        }
        if($auth_result['errorcode'] == '60022') {
            //The customer was prompted for authentication, but failed this part of the process, meaning the transaction was not authorised.
            return "The transaction failed due autohorization code is not provided. Please attempt the transaction again, ensuring the autohorization code is entered correctly, or use an alternative payment method.";
        }
        if($auth_result['errorcode'] == '70000') {
            //Authorisation for the payment was attempted but was declined by the issuing bank.
            return "The transaction failed due autohorization is attempted but the autohorization code is invalid. Please attempt the transaction again, ensuring the autohorization code is entered correctly, or use an alternative payment method.";
        }
        if($auth_result['errorcode'] == '71000') {
            //https://help.trustpayments.com/hc/en-us/articles/4403193781265-Why-has-my-payment-been-returned-a-71000-Soft-decline-response
            //https://help.trustpayments.com/hc/en-us/articles/6622494125585-Testing-Soft-declines
            // Include parenttransactionreference to the original authorisation that was soft declined (reference the AUTH, not the THREEDQUERY).
            // @TODO: @victorarthur Re-send the transaction with parenttransactionreference included
            return "The card issuer declined the request due to absence of Strong Customer Authentication (SCA). You will need to resubmit the transaction with the necessary EMV 3DS authentication.";
        }

        // if error message is still not returned, look it up in every error messages:
        // https://webapp.securetrading.net/errorcodes.html
    }

    public function processAuth()
    {
        $response = request()->all();

        // Check if the response is valid
        // Validate JWT in the response that the signature is correct
        $is_jwt_valid = $this->validateJWT($response['jwt']);

        if (!$is_jwt_valid['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'JWT validation failed.',
            ]);
        }

        //Request type: You will need to check the requesttypedescription returned in the response. Only values of “AUTH” indicate the authorisation of a payment.
        $auth_result = $is_jwt_valid['payload']['response']->first(function ($item) {
            return $item['requesttypedescription'] === 'AUTH';
        });
        
        $failiure_reason = '';
        if(isset($auth_result)) {
            if($auth_result['errorcode'] == '0') {
                // Paid for the product
                return response()->json([
                    'success' => true,
                    'transactionId' => $response['transactionreference'],
                    'payload' => $is_jwt_valid['payload'],
                ]);
            }
            else {
                $failiure_reason = $this->retrieveFailiure(
                    $auth_result['errorcode'], 
                    isset($auth_result['errordata']) ? $auth_result['errordata'] : ''
                );
            }
            
        }
        
        // response has no authentication, although the transaction might be a success, the customer not paid for the product(s)
        $sum_message = $response['errormessage'];
        if($failiure_reason) {
            $sum_message = $sum_message . '. Failiure response: ' . $failiure_reason;
        }
        return response()->json([
            'success' => false,
            'message' => $sum_message,
        ]);

    }
}
