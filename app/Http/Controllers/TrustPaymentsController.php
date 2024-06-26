<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrustPaymentsController extends Controller
{
    /*
    Hi Victor

I've gone to your url and can see this is your jwt value;

eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwYXlsb2FkIjp7ImFjY291bnR0eXBlZGVzY3JpcHRpb24iOiJFQ09NIiwiYmFzZWFtb3VudCI6IjEwNTAiLCJjdXJyZW5jeWlzbzNhIjoiR0JQIiwic2l0ZXJlZmVyZW5jZSI6InRlc3Rfbm9vbGFiczEyNzIxNiIsInJlcXVlc3R0eXBlZGVzY3JpcHRpb25zIjpbIlRIUkVFRFFVRVJZIiwiQVVUSCJdLCJiaWxsaW5nZmlyc3RuYW1lIjoiQmVydGFsYW4iLCJiaWxsaW5nbGFzdG5hbWUiOiJTXHUwMGUxbmRvciIsImxvY2FsZSI6ImVuX0dCIn0sImlhdCI6MTcxOTQwMjIwNCwiaXNzIjoiand0QG5vb2xhYnN0cmFkaW5nLmNvbSJ9.xkzz8d043Uhh36eZ5p71RBzd6j90e7Z+Hr4AgSFRxVQ=

upon checking it, it has not been created using base64url encoding so it is failing.

Please can you ensure you are creating the JWT in Base64Url encoding as per our guide: JSON Web Token

HMACSHA256(base64UrlEncode(header) + "." + base64UrlEncode(payload), secret)

The final step is to ensure the signature is Base64URL encoded.

Please can you check your encoding so we can test it again

Kind Regards
Mark Gennoe
Senior Technical Support Officer
*/
    public function generateJWT()
    {
        $jwtFieldsData = json_decode(request()->getContent(), true);
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        $payload = json_encode($jwtFieldsData);
        $payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $secret = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode('60-2771952b06f6403e7fc854ccff7a778317f79626212e659ac1b45e7e8822a78c'));

        return hash_hmac('sha256',$header . '.' . $payload, $secret, true);
    }

    public function processAuth() {
        dd(request()->all());
        // it never gets to this point
    }
}
