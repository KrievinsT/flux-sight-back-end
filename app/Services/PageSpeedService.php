<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PageSpeedService
{
    protected $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    protected $apiKey = 'AIzaSyDRFnongGdea7i4EO4Lh-PcyVoYNPhL-Hk';

    public function fetchPageSpeedData($url)
    {
        $seoData = $this->fetchCategoryData($url, 'seo');
        $performanceData = $this->fetchCategoryData($url, 'performance');

        if ($seoData && $performanceData) {
            return array_merge($seoData, $performanceData);
        }

        return null;
    }

    private function fetchCategoryData($url, $category)
    {
        $params = [
            'url' => $url,
            'strategy' => 'mobile',  // or 'desktop'
            'category' => $category,
            'key' => $this->apiKey
        ];

        $response = Http::get($this->apiUrl, $params);

        if ($response->successful()) {
            $data = $response->json();

            // Log raw response
            // Log::debug('Raw PageSpeed API response for category', ['category' => $category, 'data' => $data]);

            if ($category === 'seo') {
                return [
                    'seo' => $data['lighthouseResult']['categories']['seo']['score'] ?? null,
                ];
            } elseif ($category === 'performance') {
                return [
                    'page_speed' => $data['lighthouseResult']['categories']['performance']['score'] ?? null,
                ];
            }
        } else {
            Log::error('Failed to fetch PageSpeed data', ['category' => $category, 'url' => $url, 'response' => $response->body()]);
        }

        return null;
    }
}
