<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrustPaymentsController extends Controller
{
    public function generateJWT()
    {
        $jwtFieldsData = json_decode(request()->getContent(), true);
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        $payload = json_encode($jwtFieldsData);
        $payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $header . '.' . $payload, '60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c', true);
        $signature = base64_encode($signature);

        return $header . '.' . $payload . '.' . $signature;
    }

    public function processAuth() {
        dd(request()->all());
        // it never gets to this point
    }
}
