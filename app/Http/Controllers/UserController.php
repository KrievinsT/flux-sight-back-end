<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        // Get the logged-in user
        $loggedInUser = Auth::user();

        // Fetch users with the same company_name as the logged-in user
        $users = User::where('company_name', $loggedInUser->company_name)
                     ->select('id', 'name', 'email', 'company_name')
                     ->get();

        // Print user details
        foreach ($users as $user) {
            echo 'ID: ' . $user->id . ', Name: ' . $user->name . ', Email: ' . $user->email . ', Company Name: ' . $user->company_name . '<br>';
        }
    }
}
