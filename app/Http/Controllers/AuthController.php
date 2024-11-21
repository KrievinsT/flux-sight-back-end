<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function preRegister(Request $request)
    {
        \Log::info('preRegister: Session ID', ['session_id' => $request->session()->getId()]);

        // Store all registration data in session
        $request->session()->put('registration_data', $request->all());
        \Log::info('preRegister: After setting registration data', ['session' => $request->session()->all()]);

        return response()->json([
            'message' => 'Registration data set for testing.'
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

            $request->session()->put('login_data', [
                'email' => $user->email,
                'login' => $user->name
            ]);
            \Log::info('preLogin: Session data set', ['session' => $request->session()->get('login_data')]);

            return response()->json([
                'message' => 'Please verify 2FA to complete login.'
            ], 200);
        } else {
            \Log::error('preLogin: Invalid credentials');
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
    }

    public function register(Request $request)
    {
        \Log::info('register: Session ID', ['session_id' => $request->session()->getId()]);
        $data = $request->session()->get('registration_data');
        \Log::info('register: Retrieved session data', ['data' => $data]);
    
        if (!$data) {
            \Log::error('register: Session data not found');
            return response()->json(['message' => 'Session data not found.'], 400);
        }
    
        try {
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->phone_number = $data['phone_number'];
            $user->save();
    
            $token = $user->createToken('auth_token')->plainTextToken;
    
            $request->session()->forget('registration_data');
    
            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user->name,
                'token' => $token,
                'id' => $user->id
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Registration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
        }
    }    

    public function login(Request $request)
    {
        try {
            \Log::info('login: Session ID', ['session_id' => $request->session()->getId()]);
            $data = $request->session()->get('login_data');
            \Log::info('login: Retrieved session data', ['data' => $data]);
    
            if (!$data) {
                \Log::error('login: Session data not found');
                return response()->json(['message' => 'Session data not found.'], 400);
            }
    
            $credentials = [
                'email' => $data['email'],
                'password' => $request->password,
            ];
    
            if (!Auth::attempt($credentials)) {
                \Log::error('login: Invalid credentials', ['credentials' => $credentials]);
                return response()->json(['message' => 'Invalid login credentials.'], 401);
            }
    
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;
    
            \Log::info('login: User authenticated', ['user' => $user->email]);
    
            // Clear the session data after successful login
            $request->session()->forget('login_data');
    
            return response()->json([
                'message' => 'Login successful.',
                'user' => $user->id,
                'token' => $token
            ], 200);
        } catch (\Exception $e) {
            \Log::error('login: Unexpected error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Unexpected error occurred during login', 'error' => $e->getMessage()], 500);
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

        return response()->json([
            'message' => 'User authenticated successfully with Google',
            'user' => $user,
            'token' => $token
        ], 201);
    }
}
