<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrustPaymentsController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/jwt-generate', [TrustPaymentsController::class, 'generateJWT']);

Route::post('/process-payment', [TrustPaymentsController::class, 'processAuth']);
