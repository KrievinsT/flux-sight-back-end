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
            'url' => 'required|url',
            'title' => 'required|string',
            'username' => 'required|string',
        ]);

        try {
            $username = $request->username;
            $title = $request->title;
            set_time_limit(180);

            // Fetch and update PageSpeed data (includes website status check if needed)
            $webData = $this->pageSpeedService->fetchPageSpeedData($request->url, $username, $title);

            $is_active = $webData['is_active'] ?? $this->checkWebsiteStatus($request->url);

            Log::info('Website status checked', ['url' => $request->url, 'is_active' => $is_active]);

            // Check if the web entry already exists for the same username
            $web = Web::firstOrNew(['url' => $request->url, 'username' => $username]);
            $web->title = $request->title;
            $web->username = $username;
            $web->is_active = $is_active;
            $web->save();

            $webdataJson = [
                'created_at' => $web->updated_at->toDateTimeString(),
                'url' => $web->url,
                'title' => $web->title,
                'username' => $username,
                'data' => $webData
            ];

            $jsonFilePath = base_path("/data/{$username}.json");

            if (!File::exists($jsonFilePath)) {
                // Initialize file with an empty array
                File::put($jsonFilePath, json_encode([]));
            }

            if (File::exists($jsonFilePath)) {
                // Decode JSON and validate structure
                $existingData = json_decode(File::get($jsonFilePath), true) ?? [];
                if (!is_array($existingData)) {
                    Log::error('Invalid JSON data structure', ['jsonData' => $existingData]);
                    $existingData = [];
                }

                Log::info('Existing data', ['existingData' => $existingData]);

                // Filter entries safely with validation
                $existingData = array_filter($existingData, function ($entry) use ($title) {
                    if (!is_array($entry) || !isset($entry['title'])) {
                        Log::warning('Skipping invalid or malformed entry', ['entry' => $entry]);
                        return false;
                    }
                    return $entry['title'] !== $title;
                });

                Log::info('Filtered data', ['filteredData' => $existingData]);

                $existingData = array_values($existingData); // Re-index the array
                $existingData[] = $webdataJson; // Append new data
            } else {
                $existingData = [$webdataJson];
            }

            // Save the updated data back to the file
            File::put($jsonFilePath, json_encode($existingData, JSON_PRETTY_PRINT));

            Log::info('Website data saved successfully', ['web' => $web]);

            $this->clearData();

            return response()->json(['message' => 'Website data saved successfully', 'web' => $web], 201);
        } catch (\Exception $e) {
            Log::error('Failed to save website data', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to save website data', 'error' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $username = $request->input('username');
            $webs = Web::where('username', $username)->get();
            // Log::info('Web entries retrieved', ['webs' => $webs]);
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
        set_time_limit(180);
        try {
            $validatedData = $request->validate([
                'url' => 'sometimes|url', // Removed the unique constraint
                'title' => 'sometimes|string',
                'username' => 'sometimes|string|max:255',
            ]);
            Log::info('Validation successful', ['validated_data' => $validatedData]);
    
            // Find the web entry by ID
            Log::info('Attempting to find web entry', ['id' => $id]);
            $web = Web::findOrFail($id);
            Log::info('Web entry found', ['web' => $web]);
    
            // Update URL if provided
            if ($request->has('url')) {
                $web->url = $request->url;
                Log::info('URL updated', ['url' => $request->url]);
    
                $web->is_active = $this->checkWebsiteStatus($request->url);
                Log::info('Website status checked', ['is_active' => $web->is_active]);
            }
    
            // Update title if provided
            if ($request->has('title')) {
                $web->title = $request->title;
                Log::info('Title updated', ['title' => $request->title]);
            }
    
            $web->save();
            Log::info('Web entry saved', ['web' => $web]);
    
            $webdataJson = [
                'updated_at' => $web->updated_at->toDateTimeString(),
                'url' => $web->url,
                'title' => $web->title,
                'is_active' => $web->is_active,
            ];
    
            $username = $request->username;
            $jsonFilePath = base_path("/data/{$username}.json");
    
            $existingData = [];
            if (File::exists($jsonFilePath)) {
                $fileContent = File::get($jsonFilePath);
                $existingData = json_decode($fileContent, true) ?? [];
            }
    
            // Ensure existingData is an array
            if (!is_array($existingData)) {
                $existingData = [];
            }
    
            $dataUpdated = false;
            foreach ($existingData as &$data) {
                if (isset($data['url']) && $data['url'] == $web->url) {
                    $data = $webdataJson;
                    $dataUpdated = true;
                    break;
                }
            }
    
            if (!$dataUpdated) {
                $existingData[] = $webdataJson;
            }
    
            File::put($jsonFilePath, json_encode($existingData, JSON_PRETTY_PRINT));
    
            Log::info('Website data updated successfully', ['web' => $web]);
    
            return response()->json(['message' => 'Website data updated successfully', 'web' => $web], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error in update method', [
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred while updating website data'], 500);
        }
    }
    
    
    private function clearData()
    {
        // Logic to clear data
        Log::info('Data cleared successfully');
    }

    public function deleteRecord($id)
    {
        $record = Web::find($id);

        if (!$record) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $record->delete();

        return response()->json(['message' => 'Record deleted successfully'], 200);
    }
}
