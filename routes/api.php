<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/customers', [ApiController::class, 'customers']);
    Route::get('/invoices', [ApiController::class, 'invoices']);
    Route::get('/reports/trial-balance', [ApiController::class, 'trialBalance']);
    Route::post('/tokens/generate', [ApiController::class, 'generateToken']);
    Route::delete('/tokens/revoke', [ApiController::class, 'revokeTokens']);
});
