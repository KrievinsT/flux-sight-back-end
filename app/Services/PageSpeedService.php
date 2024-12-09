<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;



class PageSpeedService
{
    protected $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    protected $apiKey = 'AIzaSyDRFnongGdea7i4EO4Lh-PcyVoYNPhL-Hk';

    public function fetchPageSpeedData($url, $username, $title)
    {
        set_time_limit(120);

        $seoData = $this->fetchCategoryData($url, 'seo', $username, $title);
        $performanceData = $this->fetchCategoryData($url, 'performance', $username, $title);

        if ($seoData && $performanceData) {
            return array_merge($seoData, $performanceData);
        }

        return null;
    }


    private function fetchCategoryData($url, $category, $username, $title)
{
    $strategies = ['mobile', 'desktop']; 
    $results = [
        'mobile' => null,
        'desktop' => null
    ]; 

    foreach ($strategies as $strategy) {
        $params = [
            'url' => $url,
            'strategy' => $strategy,
            'category' => $category,
            'key' => $this->apiKey
        ];

        
        $response = Http::timeout(1000)->get($this->apiUrl, $params);


        Log::info('PageSpeed data request sent', ['url' => $this->apiUrl, 'params' => $params, 'strategy' => $strategy]);

        if ($response->successful()) {
            $data = $response->json();

            // Process the raw API data
            $prettyData = $this->preparePrettyData($data, $title);

            Log::info("PageSpeed data processed successfully for $strategy", ['processedData' => $prettyData]);

            // Store processed data under the corresponding key
            $results[$strategy] = $prettyData;
        } else {
            Log::error("Failed to fetch PageSpeed data for $strategy", ['response' => $response->body()]);

            // Handle error by throwing an exception or skipping
            throw new \Exception("Failed to fetch PageSpeed data for $strategy");
        }
    }

    return $results;
}



    /**
     * Prepares the data to be written to pretty.json in a simple format.
     */
    private function preparePrettyData($data, $title)
    {

        $prettyData = [

            'title' => $title,
            'id' => $data['id']

        ];

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
        
            if (isset($metric['details'])) {
                Log::info('Details test', ['details_data' => $metric['details']]);
        
                $prettyData['metrics_all'] = [
                    'title' => $metric['title'],
                    'details' => $this->extractMetricsDetails($metric['details'])
                ];
            } else {
                Log::error('Details key is missing in metrics', ['metric_data' => $metric]);
            }
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

    private function extractMetricsDetails($metrics)
    {

        $metric = [];

        foreach ($metrics['items'] as $item) {
        
            $metric[] = [
                'observedLargestContentfulPaintTs' => $item['observedLargestContentfulPaintTs'] ?? null,
                'observedLoadTs' => $item['observedLoadTs'] ?? null,
                'observedDomContentLoaded' => $item['observedDomContentLoaded'] ?? null,
                'observedLastVisualChange' => $item['observedLastVisualChange'] ?? null,
                'observedFirstPaintTs' => $item['observedFirstPaintTs'] ?? null,
                'maxPotentialFID' => $item['maxPotentialFID'] ?? null,
                'observedFirstVisualChange' => $item['observedFirstVisualChange'] ?? null,
                'speedIndex' => $item['speedIndex'] ?? null,
                'observedNavigationStartTs' => $item['observedNavigationStartTs'] ?? null,
                'observedFirstContentfulPaintTs' => $item['observedFirstContentfulPaintTs'] ?? null,
                'observedLargestContentfulPaintAllFrames' => $item['observedLargestContentfulPaintAllFrames'] ?? null,
                'timeToFirstByte' => $item['timeToFirstByte'] ?? null,
                'firstContentfulPaint' => $item['firstContentfulPaint'] ?? null,
                'observedFirstContentfulPaint' => $item['observedFirstContentfulPaint'] ?? null,
                'observedLoad' => $item['observedLoad'] ?? null,
                'observedSpeedIndex' => $item['observedSpeedIndex'] ?? null,
                'observedFirstVisualChangeTs' => $item['observedFirstVisualChangeTs'] ?? null,
                'observedTraceEnd' => $item['observedTraceEnd'] ?? null,
                'observedFirstContentfulPaintAllFrames' => $item['observedFirstContentfulPaintAllFrames'] ?? null,
                'observedLastVisualChangeTs' => $item['observedLastVisualChangeTs'] ?? null,
                'observedDomContentLoadedTs' => $item['observedDomContentLoadedTs'] ?? null,
                'observedFirstPaint' => $item['observedFirstPaint'] ?? null,
                'observedLargestContentfulPaintAllFramesTs' => $item['observedLargestContentfulPaintAllFramesTs'] ?? null,
                'largestContentfulPaint' => $item['largestContentfulPaint'] ?? null,
                'observedSpeedIndexTs' => $item['observedSpeedIndexTs'] ?? null,
                'totalBlockingTime' => $item['totalBlockingTime'] ?? null,
                'observedFirstContentfulPaintAllFramesTs' => $item['observedFirstContentfulPaintAllFramesTs'] ?? null,
                'cumulativeLayoutShift' => $item['cumulativeLayoutShift'] ?? null,
                'observedLargestContentfulPaint' => $item['observedLargestContentfulPaint'] ?? null,
                'cumulativeLayoutShiftMainFrame' => $item['cumulativeLayoutShiftMainFrame'] ?? null,
                'observedNavigationStart' => $item['observedNavigationStart'] ?? null,
                'observedTraceEndTs' => $item['observedTraceEndTs'] ?? null,
                'observedCumulativeLayoutShiftMainFrame' => $item['observedCumulativeLayoutShiftMainFrame'] ?? null,
                'observedCumulativeLayoutShift' => $item['observedCumulativeLayoutShift'] ?? null,
                'observedTimeOriginTs' => $item['observedTimeOriginTs'] ?? null,
                'observedTimeOrigin' => $item['observedTimeOrigin'] ?? null,
                'interactive' => $item['interactive'] ?? null,
                'lcpInvalidated' => $item['lcpInvalidated'] ?? null
            ];
        }
        

        return $metric;

    }

}
