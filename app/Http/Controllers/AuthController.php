<?php
namespace App\Http\Controllers;

use DB;
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
use Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function preRegister(Request $request)
    {
        \Log::info('preRegister: Received request', ['request' => $request->all()]);

        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        });

        $validator = Validator::make($input, [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|regex:/^[a-zA-Z0-9\s]*$/',
            'username' => 'required|string|regex:/^[a-zA-Z0-9\s]*$/|unique:users,username',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessages = [];

            if ($errors->has('email')) {
                $errorMessages[] = 'Email already taken or invalid.';
            }
            if ($errors->has('username')) {
                $errorMessages[] = 'Username already taken or invalid.';
            }

            return response()->json(['message' => 'Validation failed:', 'errors' => $errorMessages], 400);
        }

        return response()->json([
            'message' => 'Registration data received successfully.',
            'data' => $input
        ], 200);
    }

    public function preLogin(Request $request)
    {
        \Log::info('preLogin: Received request', ['request' => $request->all()]);

        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        });

        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        \Log::info('preLogin: Validation passed');

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $credentials = [
            $loginType => $input['login'],
            'password' => $input['password'],
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

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:15',
            'username' => 'required|string|max:255',
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
            $user->username = $data['username'];
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user->name,
                'token' => $token,
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone_number,
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
            \Log::info('login: User email', ['email' => $user->email]);
            \Log::info('login: User phone', ['phone_number' => $user->phone_number]);

            return response()->json([
                'message' => 'Login successful.',
                'id' => $user->id,
                'user' => $user->name,
                'token' => $token,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone_number,

            ], 200);
        } catch (\Exception $e) {
            \Log::error('login: Unexpected error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Unexpected error occurred during login', 'error' => $e->getMessage()], 500);
        }
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        $user = User::where('email', $email)->first();

        if ($user) {
            $token = app('auth.password.broker')->createToken($user);
            $user->sendPasswordResetNotification($token);

            // Hash the token before storing it in the 'password_reset_tokens' table
            $hashedToken = Hash::make($token);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => $hashedToken, 'created_at' => now()]
            );

            \Log::info('sendResetLinkEmail: Custom email sent', ['email' => $email]);
            return response()->json(['message' => 'Password reset link sent.'], 200);
        } else {
            \Log::error('sendResetLinkEmail: User not found with provided email');
            return response()->json(['message' => 'User not found with provided email.'], 404);
        }
    }

    public function resetPassword(Request $request)
    {
        \Log::info('resetPassword: Received request', ['request' => $request->all()]);

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string',
        ]);

        \Log::info('resetPassword: Validation passed', ['validated' => $validated]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                \Log::info('resetPassword: Processing reset for user', ['user' => $user->email]);

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                \Log::info('resetPassword: Password reset and saved for user', ['user' => $user->email]);

                // Notify the user with the new password
                $user->notify(new \App\Notifications\CustomResetPasswordNotification($password));
                \Log::info('resetPassword: New password notification sent', ['user' => $user->email]);

                // Invalidate the token
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();
                \Log::info('resetPassword: Token invalidated for user', ['email' => $request->email]);
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

    public function checkPasswordResetToken(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);
    
        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();
    
        if ($record && Hash::check($validated['token'], $record->token)) {
            return response()->json(['valid' => true]);
        } else {
            return response()->json(['valid' => false]);
        }
    }

}
