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
        // Debugging the environment variables
        \Log::info('Google Client ID: ' . env('GOOGLE_CLIENT_ID'));
        \Log::info('Google Client Secret: ' . env('GOOGLE_CLIENT_SECRET'));
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            // Log the user object for debugging
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

            return redirect()->intended('/'); // Redirect to home page after successful authentication
        } catch (\Exception $e) {
            \Log::error('Error during Google authentication:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Authentication with Google failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
