<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Web;
use App\Services\PageSpeedService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

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
            'username' => 'required|string',
        ]);

        try {
            // Fetch and update PageSpeed data (includes website status check if needed)
            $webData = $this->pageSpeedService->fetchPageSpeedData($request->url);

            if ($webData) {
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


                $webdataJson = [
                    'created_at' => $web->updated_at->toDateTimeString(),
                    'url' => $web->url,
                    'title' => $web->title,
                    'data' => $webData
                ];

                $username = $request->username;
                $jsonFilePath = base_path("/data/{$username}.json");


                if (File::exists($jsonFilePath)) {

                    $existingData = json_decode(File::get($jsonFilePath), true) ?? [];
                    $existingData[] = $webdataJson;


                } else {
                    $existingData = $webdataJson;
                }

                File::put($jsonFilePath, json_encode($existingData, JSON_PRETTY_PRINT));

                Log::info('Website data saved successfully', ['web' => $web]);

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

    public function update(Request $request, $id)
    {
        Log::info('Update request received', ['request_data' => $request->all(), 'id' => $id]);

        $validatedData = $request->validate([
            'url' => 'sometimes|url|unique:web,url,' . $id,
            'title' => 'sometimes|string',
            'username' => 'sometimes|string|max:255',
        ]);

        try {
            // Find the web entry by ID
            $web = Web::findOrFail($id);

            // Update URL and related data if the URL is provided
            if ($request->has('url')) {
                $webData = $this->pageSpeedService->fetchPageSpeedData($request->url);
                if ($webData) {
                    $is_active = $webData['is_active'] ?? $this->checkWebsiteStatus($request->url);

                    $web->url = $request->url;
                    $web->seo = $webData['seo'] ?? null;
                    $web->page_speed = $webData['page_speed'] ?? null;
                    $web->is_active = $is_active;
                } else {
                    Log::error('Failed to retrieve PageSpeed data', ['url' => $request->url]);
                    return response()->json(['message' => 'Failed to retrieve PageSpeed data'], 500);
                }
            }

            // Update title if provided
            if ($request->has('title')) {
                $web->title = $request->title;
            }

            $web->save();

            $webdataJson = [
                'updated_at' => $web->updated_at->toDateTimeString(),
                'url' => $web->url,
                'title' => $web->title,
                'seo' => $web,
                'page_speed' => $web->page_speed,
                'is_active' => $web->is_active,
            ];

            $username = $request->username;
            $jsonFilePath = base_path("/data/{$username}.json");

            if (File::exists($jsonFilePath)) {
                $existingData = json_decode(File::get($jsonFilePath), true) ?? [];
                foreach ($existingData as &$data) {
                    if ($data['url'] == $web->url) {
                        $data = $webdataJson;
                        break;
                    }
                }
            } else {
                $existingData[] = $webdataJson;
            }

            File::put($jsonFilePath, json_encode($existingData, JSON_PRETTY_PRINT));

            Log::info('Website data updated successfully', ['web' => $web]);

            return response()->json(['message' => 'Website data updated successfully', 'web' => $web], 200);
        } catch (\Exception $e) {
            Log::error('Error in update method', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred while updating website data'], 500);
        }
    }

    private function clearData()
    {
        // Logic to clear data
        Log::info('Data cleared successfully');
    }
}
