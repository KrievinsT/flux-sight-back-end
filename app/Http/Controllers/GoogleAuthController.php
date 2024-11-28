<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        \Log::info('Google Client ID: ' . env('GOOGLE_CLIENT_ID'));
        \Log::info('Google Client Secret: ' . env('GOOGLE_CLIENT_SECRET'));
        $state = bin2hex(random_bytes(16)); // Generate a random state parameter
        $redirectUrl = Socialite::driver('google')->stateless()->with(['state' => $state])->redirect()->getTargetUrl();

        \Log::info('Redirect URL: ' . $redirectUrl);
        return response()->json(['url' => $redirectUrl, 'state' => $state]);
    }

    public function handleGoogleCallback(Request $request)
    {
        $state = $request->input('state'); // Get the state parameter from the request
        \Log::info('Received state: ' . $state);
    
        try {
            $user = Socialite::driver('google')->stateless()->user();
            \Log::info('User data from Google:', (array) $user);
    
            $findUser = User::where('email', $user->email)->first();
    
            if ($findUser) {
                Auth::login($findUser);
                $token = $findUser->createToken('auth_token')->plainTextToken;
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'password' => encrypt('my-google'),
                ]);
                Auth::login($newUser);
                $token = $newUser->createToken('auth_token')->plainTextToken;
            }
    
            \Log::info('Generated token: ' . $token);
            return redirect()->away('https://flux-sight.vercel.app/dashboard?token=' . $token);
        } catch (\Exception $e) {
            \Log::error('Error during Google authentication:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Authentication with Google failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }    

}
