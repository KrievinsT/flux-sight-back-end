<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Storage;
use Illuminate\Support\Facades\Http;

class StorageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'seo' => 'required|string',
            'page_speed' => 'required|numeric',
        ]);

        // Check if the website is active
        $is_active = $this->checkWebsiteStatus($request->url);

        $storage = new Storage;
        $storage->user_id = $request->user()->id;
        $storage->url = $request->url;
        $storage->seo = $request->seo;
        $storage->page_speed = $request->page_speed;
        $storage->is_active = $is_active;
        $storage->role_id = 1; // Role ID 1 for admin
        $storage->save();

        return response()->json(['message' => 'Website data saved successfully', 'storage' => $storage], 201);
    }

    public function index(Request $request)
    {
        $storages = Storage::all();
        return response()->json($storages); // Return the storage entries as a JSON response
    }

    private function checkWebsiteStatus($url)
    {
        try {
            $response = Http::get($url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
