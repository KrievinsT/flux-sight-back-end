<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $username = $request->input('username');
        // Log::info('Fetching users', ['username' => $username]);
        
        if ($username) {
            $users = User::select('id', 'name', 'email', 'phone_number', 'username')
                          ->where('username', $username)
                          ->get();
            // Log::info('Users found', ['users' => $users]);
        } else {
            // Log::info('No username provided');
            return response()->json([]);
        }
        
        // Return users as JSON
        return response()->json($users);
    }    

    public function update(Request $request, $id)
    {
        Log::info('Update request received', ['request_data' => $request->all(), 'id' => $id]);
    
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255',
            'phone_number' => 'nullable|string|max:15',
            'username' => 'sometimes|string|max:255',
        ]);
    
        try {
            // Find the user by ID
            $user = User::findOrFail($id);
    
            // Update only the fields that were provided in the request
            $updated = false;
            foreach ($validatedData as $key => $value) {
                if ($user->$key !== $value) {
                    $user->$key = $value;
                    $updated = true;
                }
            }
    
            if ($updated) {
                $user->save();
                Log::info('User updated successfully', ['user' => $user]);
                return response()->json(['message' => 'User updated successfully', 'user' => $user]);
            } else {
                Log::info('No changes detected for user', ['user' => $user]);
                return response()->json(['message' => 'No changes detected for user', 'user' => $user]);
            }
        } catch (\Exception $e) {
            Log::error('Error in update method', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred while updating user data'], 500);
        }
    }
}
