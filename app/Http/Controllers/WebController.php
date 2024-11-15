<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Assuming you have a User model

class WebController extends Controller
{
    public function index(Request $request)
    {
        // Assuming 'user_id' is stored in the session or a cookie
        $localUserId = $request->session()->get('user_id'); // Retrieve user_id from session

        // Fetch all users with the same user_id from the table
        $users = User::where('user_id', $localUserId)->get();

        return response()->json($users); // Return the users as a JSON response
    }
}
