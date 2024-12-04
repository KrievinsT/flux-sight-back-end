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

        $storage = new Storage;
        $storage->user_id = $request->user()->id->nullable();
        $storage->web_id = $request->web()->id;
        $storage->save();

        return response()->json(['message' => 'Website data saved successfully', 'storage' => $storage], 201);
    }

    public function index(Request $request)
    {
        $storages = Storage::all();
        return response()->json($storages);
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
