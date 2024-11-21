<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TwoFactorAuthController extends Controller
{
    public function generate2FACode(Request $request) {
        \Log::info('generate2FACode: Session ID', ['session_id' => $request->session()->getId()]);
        
        // Prioritize registration_data over login_data
        $data = $request->session()->get('registration_data') ?? $request->session()->get('login_data');
        \Log::info('generate2FACode: Retrieved session data', ['data' => $data]);
        
        if (!$data) {
            \Log::error('generate2FACode: Session data not found');
            return response()->json(['message' => 'Session data not found.'], 400);
        }
    
        $email = $data['email'];
        $twoFactorCode = random_int(100000, 999999);
        
        $request->session()->put('two_factor_code', $twoFactorCode);
        $request->session()->put('two_factor_expires_at', Carbon::now()->addMinutes(10));
        
        \Log::info('generate2FACode: Generated 2FA code', ['code' => $twoFactorCode]);
        
        try {
            Mail::send([], [], function ($message) use ($email, $twoFactorCode) {
                $message->to($email)
                        ->subject('Your 2FA Code')
                        ->text('Your two-factor authentication code is: ' . $twoFactorCode);
            });
            \Log::info('generate2FACode: Email sent', ['email' => $email]);
        } catch (\Exception $e) {
            \Log::error('generate2FACode: Failed to send email', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to send 2FA email.'], 500);
        }
        
        return response()->json(['message' => '2FA code sent successfully to ' . $email], 200);
    }
    
    

    public function verify2FACode(Request $request)
    {
        try {
            Log::info('Received request to verify 2FA code', ['request' => $request->all()]);

            $request->validate([
                'two_factor_code' => 'required|numeric',
            ]);

            $sessionCode = $request->session()->get('two_factor_code');
            $sessionExpiresAt = $request->session()->get('two_factor_expires_at');

            if (!$sessionCode || !$sessionExpiresAt) {
                Log::error('2FA session data not found');
                return response()->json(['message' => '2FA session data not found.'], 400);
            }

            if (Carbon::now()->gt($sessionExpiresAt)) {
                Log::error('The 2FA code has expired');
                return response()->json(['message' => 'The 2FA code has expired.'], 401);
            }

            if ($request->two_factor_code != $sessionCode) {
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
