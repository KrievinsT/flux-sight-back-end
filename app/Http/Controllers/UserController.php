<?php

namespace App\Http\Controllers;

use App\Models\Web;
use File;
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
            'user' => 'sometimes|string|max:255', // Added to validate the user field
            'email' => 'sometimes|string|email|max:255',
            'phone' => 'nullable|string|max:15',
            'username' => 'sometimes|string|max:255',
            'originalUsername' => 'required|string|max:255' // Ensure originalUsername is provided
        ]);
    
        try {
            // Find the user by ID
            $user = User::findOrFail($id);
    
            // Store the original username
            $originalUsername = $validatedData['originalUsername'];
    
            // Update only the fields that were provided in the request
            $updated = false;
            foreach ($validatedData as $key => $value) {
                $field = $key;
    
                if ($key === 'phone') {
                    $field = 'phone_number';
                }
    
                // Map 'user' field to 'name' field in the database
                if ($key === 'user') {
                    $field = 'name';
                }
    
                if ($user->$field !== $value) {
                    Log::info('Field changed', ['field' => $field, 'old' => $user->$field, 'new' => $value]);
                    $user->$field = $value;
                    $updated = true;
                } else {
                    Log::info('No change for field', ['field' => $field, 'value' => $value]);
                }
            }
    
            if ($updated) {
                // Update the JSON file before saving the new username
                $jsonFilePath = base_path("data/{$originalUsername}.json");
                Log::info('Looking for JSON file at path', ['path' => $jsonFilePath]);
                if (File::exists($jsonFilePath)) {
                    $jsonData = json_decode(File::get($jsonFilePath), true);
                    $jsonData['name'] = $user->name; // Update the name attribute
                    File::put($jsonFilePath, json_encode($jsonData, JSON_PRETTY_PRINT));
                    Log::info('JSON file updated successfully', ['file' => $jsonFilePath]);
    
                    // Rename the JSON file to the new username
                    $newJsonFilePath = base_path("data/{$user->username}.json");
                    File::move($jsonFilePath, $newJsonFilePath);
                    Log::info('JSON file renamed successfully', ['newFile' => $newJsonFilePath]);
                } else {
                    Log::warning('JSON file not found', ['file' => $jsonFilePath]);
                }
    
                // Now save the user with the new username
                $user->save();
                Log::info('User updated successfully', ['user' => $user]);
    
                // Update the corresponding username in the web table
                $this->updateWebTableUsername($originalUsername, $user->username);
    
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
    
    private function updateWebTableUsername($originalUsername, $newUsername)
    {
        // Assuming you have a WebTable model representing the web table
        $webTable = Web::where('username', $originalUsername)->first();
        if ($webTable) {
            $webTable->username = $newUsername;
            $webTable->save();
            Log::info('Web table username updated successfully', ['originalUsername' => $originalUsername, 'newUsername' => $newUsername]);
        } else {
            Log::warning('Web table entry not found for username', ['username' => $originalUsername]);
        }
    }
    

    public function checkUserDetails(Request $request)
    {
        $id = $request->input('id');
        
        if (!$id) {
            return response()->json(['message' => 'User ID is required.'], 400);
        }
    
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
    
        $messages = [];
    
        if (empty($user->username)) {
            $messages[] = 'Username is not set.';
        }
    
        if (empty($user->phone_number)) {
            $messages[] = 'Phone number is not inputted.';
        }
    
        if (!empty($messages)) {
            return response()->json(['messages' => $messages]);
        }
    }

    public function updateNotifications(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'sendSMS' => 'required|boolean',
            'sendEmail' => 'required|boolean',
            'sendAlerts' => 'required|boolean',
        ]);
    
        $user = User::find($request->user_id);
        $user->sendSMS = $request->sendSMS;
        $user->sendEmail = $request->sendEmail;
        $user->sendAlerts = $request->sendAlerts;
        $user->save();
    
        return response()->json(['message' => 'Notification settings updated successfully'], 200);
    }
    

    public function getNotificationSettings(Request $request)
    {
        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        return response()->json([
            'sendSMS' => $user->sendSMS,
            'sendEmail' => $user->sendEmail,
            'sendAlerts' => $user->sendAlerts,
        ], 200);
    }
    
}
