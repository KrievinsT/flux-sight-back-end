<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','string','email','max:255','unique:users'],
            'password' => ['required','string','min:8','confirmed'],
            'phone_number' => ['required','string'],
            'auth_class' => ['varchar','integer']
        ]);

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password =  Hash::make($request->password);
        $user->phone_number = $request->phone_number;
        $user->auth_class = $request->auth_class;
        $user->save();
        $token = $user->create_token('auth_token')->access_token;

        return response()->json([
           'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }
    public function login(Request $request)
    {
        $credentials = $request->only('name', 'password');
        
        if (Auth::attempt($credentials)) {

            $user = Auth::user();

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'username' => $user->username
            ]);
        } else {
            return response()->json([
               'status' => 'error',
               'message' => 'Invalid credentials'
            ], 401);
        }
    }


    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $user = Socialite::driver('google')->user();
        $findUser = User::where('email', $user->email)->first();

        if ($findUser) {
            Auth::login($findUser);
            $token = $findUser->create_token('auth_token')->access_token;
        } else {
            $newUser = User::create([
                'name' => $user->name,
                'email' => $user->email,
                'google_id'=> $user->id,
                'password' => encrypt('my-google'),
            ]);
            Auth::login($newUser);
            $token = $newUser->create_token('auth_token')->access_token;
        }

        return response()->json([
            'message' => 'User authenticated successfully with Google',
            'user' => $user,
            'token' => $token
        ], 201);
    }
}
