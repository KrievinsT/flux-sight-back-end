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
        try {
            $request->validate([
                'name' => ['required','string','max:255'],
                'email' => ['required','string','email','max:255','unique:users'],
                'password' => ['required','string','min:8','confirmed'],
                'phone_number' => ['required','string'],
            ]);
    
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password =  Hash::make($request->password);
            $user->phone_number = $request->phone_number;
            $user->save();
            $token = $user->createToken('auth_token')->plainTextToken;
    
    
            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user->name,
                'token' => $token,
                'id' => $user->id
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);
    
        $loginType = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $credentials = [
            $loginType => $request->input('login'),
            'password' => $request->input('password'),
        ];
    
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'user' => $user->name
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
