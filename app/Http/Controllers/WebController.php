<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Web;
use App\Services\PageSpeedService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebController extends Controller
{
    protected $pageSpeedService;

    public function __construct(PageSpeedService $pageSpeedService)
    {
        $this->pageSpeedService = $pageSpeedService;
    }

    public function store(Request $request)
    {
        Log::info('Store request received', ['request_data' => $request->all()]);

        $request->validate([
            'url' => 'required|url|unique:web,url', 
            'title' => 'required|string',
        ]);

        try {
            // Fetch and update PageSpeed data (includes website status check if needed)
            $webData = $this->pageSpeedService->fetchPageSpeedData($request->url);
        
            if ($webData) {
                // Use the `is_active` status from fetched data or add a fallback check
                $is_active = $webData['is_active'] ?? $this->checkWebsiteStatus($request->url);
                Log::info('Website status checked', ['url' => $request->url, 'is_active' => $is_active]);
        
                // Check if the web entry already exists
                $web = Web::firstOrNew(['url' => $request->url]);
                $web->title = $request->title;
        
                // Assign fetched data to the web instance
                $web->seo = $webData['seo'] ?? null;
                $web->page_speed = $webData['page_speed'] ?? null;
                $web->is_active = $is_active;
                $web->save();
        
                Log::info('Website data saved successfully', ['web' => $web]);
        
                // Clear data on success
                $this->clearData();
        
                return response()->json(['message' => 'Website data saved successfully', 'web' => $web], 201);
            } else {
                Log::error('Failed to retrieve PageSpeed data', ['url' => $request->url]);
                return response()->json(['message' => 'Failed to retrieve PageSpeed data'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in store method', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred while saving website data'], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $webs = Web::all();
            Log::info('Web entries retrieved', ['webs' => $webs]);
            return response()->json($webs);
        } catch (\Exception $e) {
            Log::error('Error in index method', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred while retrieving web entries'], 500);
        }
    }

    private function checkWebsiteStatus($url)
    {
        try {
            $response = Http::get($url);
            $is_active = $response->successful();
            Log::info('Website status checked', ['url' => $url, 'is_active' => $is_active]);
            return $is_active;
        } catch (\Exception $e) {
            Log::error('Error in checkWebsiteStatus method', ['exception' => $e->getMessage()]);
            return false;
        }
    }

    // Method to clear data
    private function clearData()
    {
        // Logic to clear data
        Log::info('Data cleared successfully');
    }
}
