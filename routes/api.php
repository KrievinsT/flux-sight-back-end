<?php
use App\Http\Controllers\JsonController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\TwilioSMSController;

//pre routes/google routes
Route::post('/pre-register', [AuthController::class, 'preRegister']);
Route::post('/pre-login', [AuthController::class, 'preLogin']);
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// user login/register
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

//reset pass routes
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

//verify routes
Route::post('/2fa/generate', [TwoFactorAuthController::class, 'generate2FACode']);
Route::post('/2fa/verify', [TwoFactorAuthController::class, 'verify2FACode']);

//web save routes
Route::post('/web/store', [WebController::class, 'store']);
Route::post('/storage/store', [StorageController::class, 'store']);

//get all routes
Route::get('/web', [WebController::class, 'index']);
Route::get('/storage', [StorageController::class, 'index']);
Route::get('/users', [UserController::class, 'index']);

// json routes
Route::post('/update-profile/{username}', [JsonController ::class, 'updateProfileData']);

// wip sms routes
Route::get('/sendSMS', [TwilioSMSController::class, 'index']);

//update routes
Route::put('/users/{id}', [UserController::class, 'update']);
Route::put('/web/{id}', [WebController::class, 'update']);

// Route::middleware('auth:sanctum')->group(function () {
// });
// check reset password token
Route::post('/check-password-reset-token', [AuthController::class, 'checkPasswordResetToken']);

//delete
Route::delete('/delete-record/{id}', [WebController::class, 'deleteRecord']);

//notifs
Route::post('/notifications', [UserController::class, 'checkUserDetails']);

//if allow sms or email notifications
Route::put('/user/notifications', [UserController::class, 'updateNotifications']);
Route::get('/user/notifications', [UserController::class, 'getNotificationSettings']);