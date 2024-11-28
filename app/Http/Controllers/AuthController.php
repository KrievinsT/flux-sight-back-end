<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
    public function preRegister(Request $request)
    {
        \Log::info('preRegister: Received request', ['request' => $request->all()]);

        return response()->json([
            'message' => 'Registration data received successfully.',
            'data' => $request->all()
        ], 200);
    }

    public function preLogin(Request $request)
    {
        \Log::info('preLogin: Received request', ['request' => $request->all()]);
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);
    
        \Log::info('preLogin: Validation passed');
    
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];
    
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            \Log::info('preLogin: User authenticated', ['user' => $user->email]);
    
            try {
                $token = JWTAuth::fromUser($user);
    
                return response()->json([
                    'message' => 'Login successful. Please verify 2FA to complete login.',
                    'token' => $token,
                    'email' => $user->email,
                ], 200);
            } catch (\Exception $e) {
                \Log::error('preLogin: Token generation failed', ['error' => $e->getMessage()]);
                return response()->json(['message' => 'Could not create token.', 'error' => $e->getMessage()], 500);
            }
        } else {
            \Log::error('preLogin: Invalid credentials');
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
    }    
    
    public function register(Request $request)
    {
        \Log::info('register: Request data', ['data' => $request->all()]);
    
        $data = $request->all();
    
        // Validate the request data
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:15',
        ]);
    
        if ($validator->fails()) {
            \Log::error('register: Validation failed', ['errors' => $validator->errors()]);
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 400);
        }
    
        try {
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->phone_number = $data['phone'];
            $user->save();
    
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user->name,
                'token' => $token,
                'id' => $user->id,
                'company_name' => $user->company_name
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Registration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
        }
    }    

    public function login(Request $request)
    {
        try {
            \Log::info('login: Request data', ['data' => $request->all()]);
    
            $data = $request->all();
    
            if (!$data) {
                \Log::error('login: Request data not found');
                return response()->json(['message' => 'Request data not found.'], 400);
            }
    
            $credentials = [
                'email' => $data['email'],
                'password' => $data['password'],
            ];
    
            if (!Auth::attempt($credentials)) {
                \Log::error('login: Invalid credentials', ['credentials' => $credentials]);
                return response()->json(['message' => 'Invalid login credentials.'], 401);
            }
    
            $user = Auth::user();
    
            \Log::info('login: Authenticated user object', ['user' => $user]);
            
            $token = $user->createToken('auth_token')->plainTextToken;
    
            \Log::info('login: User authenticated', ['user' => $user->email]);

            \Log::info('login: User name', ['name' => $user->name]);
    
            return response()->json([
                'message' => 'Login successful.',
                'id' => $user->id,
                'user' => $user->name,
                'token' => $token,
                'company_name' => $user->company_name
            ], 200);
        } catch (\Exception $e) {
            \Log::error('login: Unexpected error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Unexpected error occurred during login', 'error' => $e->getMessage()], 500);
        }
    }
    
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));
        if ($status == Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent.'], 200);
        } else {
            return response()->json(['message' => 'Unable to send reset link.'], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        \Log::info('resetPassword: Received request', ['request' => $request->all()]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string',
        ]);

        \Log::info('resetPassword: Validation passed');

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        \Log::info('resetPassword: Status', ['status' => $status]);

        if ($status == Password::PASSWORD_RESET) {
            \Log::info('resetPassword: Password reset successful');
            return response()->json(['message' => 'Password has been reset.'], 200);
        } else {
            \Log::error('resetPassword: Password reset failed', ['status' => $status]);
            return response()->json(['message' => 'Unable to reset password.'], 500);
        }
    }
}
