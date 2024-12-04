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
        $state = bin2hex(random_bytes(16)); // Generate a random state parameter
        $redirectUrl = Socialite::driver('google')->stateless()->with(['state' => $state])->redirect()->getTargetUrl();
        return response()->json(['url' => $redirectUrl, 'state' => $state]);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $user = Socialite::driver('google')->stateless()->user();
            $findUser = User::where('email', $user->email)->first();
    
            if ($findUser) {
                Auth::login($findUser);
                $token = $findUser->createToken('auth_token')->plainTextToken;
                $userDetails = ['id' => $findUser->id, 'name' => $findUser->name, 'email' => $findUser->email];
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'password' => encrypt('my-google'),
                ]);
                Auth::login($newUser);
                $token = $newUser->createToken('auth_token')->plainTextToken;
                $userDetails = ['id' => $newUser->id, 'name' => $newUser->name, 'email' => $newUser->email];
            }

            $query = http_build_query([
                'token' => $token,
                'id' => $userDetails['id'],
                'name' => $userDetails['name'],
                'email' => $userDetails['email']
            ]);
    
            return redirect()->away('http://localhost:3000/login?' . $query);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Authentication with Google failed', 'error' => $e->getMessage()], 500);
        }
    }
}
