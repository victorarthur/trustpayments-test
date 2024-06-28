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

        /*
         * HMACSHA256(
         *    base64UrlEncode(header) + "." +
         *    base64UrlEncode(payload),
         *    secret)
         */

        $secret = $this->base64UrlEncode($secret);

        // Create the HMACSHA256 signature
        $signature = hash_hmac('sha256', $encodedHeader . "." . $encodedPayload, $secret);

        // Encode the signature using base64UrlEncode
        return $this->base64UrlEncode($signature);
    }

    public function generateJWT()
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode(json_decode(request()->getContent(), true));
        $secret = '60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c';

//jwt@noolabstrading.com
//60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c

        $signature = $this->createJwtSignature($header, $payload, $secret); // returns with base64UrlEncoded signature

        return $this->base64UrlEncode($header) . "." . $this->base64UrlEncode($payload) . "." . $signature;
    }

    public function processAuth()
    {
        dd(request()->all());
        // it never gets to this point
    }
}
