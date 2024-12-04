<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;



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

            // Log raw response and save it to raw JSON file
            try {
                $directoryPath = base_path("data");
                $filePath = "{$directoryPath}/test_data.json"; // Raw data file
                $prettyFilePath = "{$directoryPath}/pretty.json"; // Pretty data file

                // Ensure the directory exists
                if (!File::exists($directoryPath)) {
                    File::makeDirectory($directoryPath, 0755, true);
                }

                // Write raw data to test_data.json
                File::put($filePath, json_encode($data, JSON_PRETTY_PRINT));

                Log::info('PageSpeed data saved successfully', ['filePath' => $filePath]);

                // Process metrics and create a simple formatted result for pretty.json
                $prettyData = $this->preparePrettyData($data);

                // Write the formatted log-like data to pretty.json
                File::put($prettyFilePath, json_encode($prettyData, JSON_PRETTY_PRINT));
                Log::info('Pretty data saved successfully', ['filePath' => $prettyFilePath]);

            } catch (\Exception $e) {
                Log::error('Error saving or processing PageSpeed data', ['error' => $e->getMessage()]);
            }
        } else {
            Log::error('Failed to fetch PageSpeed data', [
                'category' => $category,
                'url' => $url,
                'response' => $response->body()
            ]);
        }

        return null;
    }

    /**
     * Prepares the data to be written to pretty.json in a simple format.
     */
    private function preparePrettyData($data)
    {

        // Metric for load time

        if (isset($data['loadingExperience']['metrics'])) {
            $metrics = $data['loadingExperience']['metrics'];

            foreach ($metrics as $metricName => $metricData) {
                if (isset($metricData['distributions'])) {
                    // Calculate the weighted average
                    $weightedAverage = $this->calculateWeightedAverage($metricData['distributions']);
                    $percentile = $metricData['percentile'] ?? 'N/A';
                    $category = $metricData['category'] ?? 'Unknown';

                    // Format the metric data simply with only the needed information
                    $prettyData['metrics'][$metricName] = [
                        'Weighted Average' => number_format($weightedAverage, 4) . ' ms', // format weighted average
                        'Percentile' => $percentile . ' ms',
                        'Category' => $category
                    ];
                }
            }
        }

        // Boot up time

        if (isset($data['lighthouseResult']['audits']['bootup-time'])) {
            $bootupTime = $data['lighthouseResult']['audits']['bootup-time'];

            $prettyData['bootup-time'] = [
                'title' => $bootupTime['title'],
                'description' => $bootupTime['description'],
                'displayValue' => $bootupTime['displayValue'],
                'details' => $this->extractBootupTimeDetails($bootupTime['details'])
            ];
        }

        // Server response Time

        if (isset($data['lighthouseResult']['audits']['server-response-time'])) {
            $servertupTime = $data['lighthouseResult']['audits']['server-response-time'];

            $prettyData['server-response-time'] = [
                'title' => $servertupTime['title'],
                'description' => $servertupTime['displayValue']
            ];
        }

        // Overal Diagnosis

        if (isset($data['lighthouseResult']['audits']['diagnostics'])) {
            $diagnostics = $data['lighthouseResult']['audits']['diagnostics'];

            $prettyData['diagnostics'] = [
                'title' => $diagnostics['title'],
                'description' => $diagnostics['description'],
                'score' => $diagnostics['score'],
                'scoreDisplayMode' => $diagnostics['scoreDisplayMode'],
                'details' => $this->extractDiagnosticsDetails($diagnostics['details'])
            ];
        }

        // Detailed Time report for else

        if (isset($data['lighthouseResult']['audits']['metrics'])) {
            $metric = $data['lighthouseResult']['audits']['metrics'];

            $prettyData['metrics'] = [
                'title' => $metric['title'],
                'details' => $this->extractMetricsDetails($metrics['details'])
            ];
        }

        return $prettyData;
    }

    /**
     * Extracts the necessary details from the bootup-time details section.
    */

    private function extractBootupTimeDetails($details)
    {
        $items = [];
        foreach ($details['items'] as $item) {
            $items[] = [
                'url' => $item['url'],
                'total' => number_format($item['total'], 4) . ' ms',
                'scriptParseCompile' => number_format($item['scriptParseCompile'], 4) . ' ms',
                'scripting' => number_format($item['scripting'], 4) . ' ms'
            ];
        }

        return $items;
    }

    /**
     * Extracts the necessary details from the diagnostics details section.
    */

    private function extractDiagnosticsDetails($details)
    {
        $items = [];
        foreach ($details['items'] as $item) {
            $items[] = [
                'numTasksOver500ms' => $item['numTasksOver500ms'],
                'maxServerLatency' => $item['maxServerLatency'],
                'numRequests' => $item['numRequests'],
                'round_trip_time' => number_format($item['rtt'], 7) . ' ms',
                'numOfStylesheets' => $item['numStylesheets'],
                'numOfScripts' => $item['numScripts'],    
                'mainDocumentTransferSize' => number_format($item['mainDocumentTransferSize'], 0) . ' bytes',
                'max_round_trip_time' => number_format($item['maxRtt'], 5) . ' ms',
                'numTasksOver50ms' => $item['numTasksOver50ms'],
                'totalByteWeight' => number_format($item['totalByteWeight'], 0) . ' bytes',
                'totalTaskTime' => number_format($item['totalTaskTime'], 4) . ' ms',
                'numTasksOver25ms' => $item['numTasksOver25ms'],
                'numTasks' => $item['numTasks'],
                'numTasksOver100ms' => $item['numTasksOver100ms'],
                'numFonts' => $item['numFonts'],
                'numTasksOver10ms' => $item['numTasksOver10ms'],
                'throughput' => number_format($item['throughput'], 0)
            ];
        }

        return $items;
    }

    /**
     * Calculates the weighted average for the given distributions.
     */
    private function calculateWeightedAverage(array $distributions)
    {
        $total = 0;

        foreach ($distributions as $range) {
            $midpoint = isset($range['max'])
                ? ($range['min'] + $range['max']) / 2
                : $range['min']; // Use minimum value for open-ended ranges
            $total += $midpoint * $range['proportion'];
        }

        return $total;
    }

    private function extractMetricsDetails ($metrics) {

        $metric = [];

        foreach ($metrics['items'] as $item) {

            $metric [] = [

                'observedLargestContentfulPaintAllFrames' => $item['observedLargestContentfulPaintAllFramesT'],
                'speedIndex' => $item['speedIndex'],
                'observedFirstPaint' => $item['observedFirstPaint'],
                'maxPotentialFID' => $item['maxPotentialFID'],
                'observedTraceEnd' => $item['observedTraceEnd'],
                'cumulativeLayoutShift' => $item['cumulativeLayoutShift'],
                'observedNavigationStart' => $item['observedNavigationStart'],
                'observedLargestContentfulPaintAllFrames' => $item['observedLargestContentfulPaintAllFrames'],
                'observedTraceEndTs' => $item['observedTraceEndTs'],
                'observedFirstContentfulPaintAllFramesTs' => $item['observedFirstContentfulPaintAllFramesTs'],
                'observedLastVisualChangeTs' => $item['observedLastVisualChangeTs'],
                'observedTimeOrigin' => $item['observedTimeOrigin'],
                'observedFirstContentfulPaintTs' => $item['observedFirstContentfulPaintTs'],
                


            ];

        }

    }

}
