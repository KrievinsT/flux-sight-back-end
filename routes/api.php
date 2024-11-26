<?php

use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\TwilioSMSController;

Route::get('/sendSMS', [TwilioSMSController::class, 'index']);

Route::middleware(['web'])->group(function () {
    
    Route::post('/pre-register', [AuthController::class, 'preRegister']);

    Route::post('/register', [AuthController::class, 'register']);

    Route::post('/pre-login', [AuthController::class, 'preLogin']);

    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/2fa/generate', [TwoFactorAuthController::class, 'generate2FACode']);

    Route::post('/2fa/verify', [TwoFactorAuthController::class, 'verify2FACode']);

    Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);

    Route::post('/password/reset', [AuthController::class, 'resetPassword']);

    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);

    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

});

// Web routes
Route::post('/web/store', [WebController::class, 'store']);
Route::get('/web', [WebController::class, 'index']);

// Storage routes
Route::post('/storage/store', [StorageController::class, 'store']);
Route::get('/storage', [StorageController::class, 'index']);
