<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TwoFactorAuthController extends Controller
{
    public function generate2FACode(Request $request) {
        \Log::info('generate2FACode: Request received', ['request' => $request->all()]);
    
        $data = $request->input('registration_data') ?? $request->input('login_data');
        \Log::info('generate2FACode: Retrieved input data', ['data' => $data]);
    
        if (!$data) {
            \Log::error('generate2FACode: Input data not found');
            return response()->json(['message' => 'Input data not found.'], 400);
        }
    
        if (isset($data['email'])) {
            $email = $data['email'];
        } elseif (isset($data['login'])) {
            // If 'login' is used, retrieve the user by their login (e.g., email or username)
            $user = User::where('email', $data['login'])->orWhere('name', $data['login'])->first();
            if ($user) {
                $email = $user->email;
            } else {
                \Log::error('generate2FACode: User not found');
                return response()->json(['message' => 'User not found.'], 400);
            }
        } else {
            \Log::error('generate2FACode: Email or login not provided');
            return response()->json(['message' => 'Email or login not provided.'], 400);
        }
    
        $twoFactorCode = random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);
    
        $token = Str::random(40);
        cache()->put($token, ['code' => $twoFactorCode, 'expires_at' => $expiresAt], $expiresAt);
    
        \Log::info('generate2FACode: Generated 2FA code and token', ['code' => $twoFactorCode, 'token' => $token]);
    
        try {
            Mail::send([], [], function ($message) use ($email, $twoFactorCode) {
                $message->to($email)
                        ->from('toms.ricards@vtdt.edu.lv', 'FLUXSIGHT TEAM:')
                        ->subject('Your 2FA Code')
                        ->text('Your two-factor authentication code is: ' . $twoFactorCode);
            });
            \Log::info('generate2FACode: Email sent', ['email' => $email]);
        } catch (\Exception $e) {
            \Log::error('generate2FACode: Failed to send email', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to send 2FA email.'], 500);
        }
    
        // Include the 2FA code in the response for testing
        return response()->json(['message' => '2FA code sent successfully to ' . $email, 'token' => $token, 'twoFactorCode' => $twoFactorCode], 200);
    }
      
    public function verify2FACode(Request $request)
    {
        try {
            Log::info('Received request to verify 2FA code', ['request' => $request->all()]);

            $request->validate([
                'two_factor_code' => 'required|numeric',
                'token' => 'required|string',
            ]);

            $cachedData = cache()->get($request->token);

            if (!$cachedData) {
                Log::error('2FA token not found or expired');
                return response()->json(['message' => '2FA token not found or expired.'], 400);
            }

            if (Carbon::now()->gt($cachedData['expires_at'])) {
                Log::error('The 2FA code has expired');
                return response()->json(['message' => 'The 2FA code has expired.'], 401);
            }

            if ($request->two_factor_code != $cachedData['code']) {
                Log::error('Invalid 2FA code');
                return response()->json(['message' => 'Invalid 2FA code.'], 401);
            }

            Log::info('2FA verified successfully');
            return response()->json(['message' => '2FA verified successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to verify 2FA code', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to verify 2FA code.'], 500);
        }
    }
}
