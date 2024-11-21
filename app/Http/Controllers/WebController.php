<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Web; // Assuming you have a Web model
use Illuminate\Support\Facades\Http;

class WebController extends Controller
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

        $web = new Web;
        $web->url = $request->url;
        $web->seo = $request->seo;
        $web->page_speed = $request->page_speed;
        $web->is_active = $is_active;
        $web->save();

        return response()->json(['message' => 'Website data saved successfully', 'web' => $web], 201);
    }

    public function index(Request $request)
    {
        $webs = Web::all();
        return response()->json($webs); // Return the web entries as a JSON response
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
