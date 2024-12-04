<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JsonController extends Controller
{
    public function getUserJson($username)
    {
        $directoryPath = base_path("data");
        $filePath = "{$directoryPath}/{$username}.json";

        Log::info('Json Has Been Passed Succesfully for ->', ['username' => $username]);

        if (File::exists($filePath)) {
            $content = File::get($filePath);
            return response()->json(json_decode($content, true));
        } else {
            return response()->json(['message' => 'Profile not found'], 404);
        }
    }

    public function updateProfileData(Request $request, $username)
    {
        $directoryPath = base_path("data");
        $filePath = "{$directoryPath}/{$username}.json";

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        $profileData = $request->all();
        $profileData['username'] = $username;
        $profileData['updated_at'] = now();

        if (!File::exists($filePath)) {
            $profileData['created_at'] = now();
            File::put($filePath, json_encode($profileData, JSON_PRETTY_PRINT));
            return response()->json(['message' => 'Profile JSON file created successfully']);
        } else {
            File::put($filePath, json_encode($profileData, JSON_PRETTY_PRINT));
            return response()->json(['message' => 'Profile JSON file updated successfully']);
        }
    }
}
