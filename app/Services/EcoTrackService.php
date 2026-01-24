<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class EcoTrackService
{
    /**
     * Get the last tracking status for a given tracking number.
     *
     * @param string $trackingNumber
     * @return array
     * @throws Exception
     */
    public function getTrackingStatus(string $trackingNumber): array
    {
        $url = "https://suivi.ecotrack.dz/suivi/{$trackingNumber}";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            ])->get($url);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch tracking data: " . $response->status());
            }

            $html = $response->body();

            // Extract the current status using regex
            // Pattern: <li class="current"> ... <a href="#"> STATUS <span class="desc"><b>DATE</b></span> ... </li>

            if (preg_match('/<li[^>]*class="[^"]*current[^"]*"[^>]*>(.*?)<\/li>/s', $html, $matches)) {
                $liContent = $matches[1];

                $status = 'Unknown';
                $date = null;

                // Extract Status Text (inside <a> but before <span class="desc">)
                if (preg_match('/<a[^>]*>([^<]+)/s', $liContent, $statusMatch)) {
                    $status = trim($statusMatch[1]);
                }

                // Extract Date (inside <span class="desc"><b>)
                if (preg_match('/<span[^>]*class="desc"[^>]*>.*?<b>(.*?)<\//s', $liContent, $dateMatch)) {
                    $date = trim($dateMatch[1]);
                }

                return [
                    'tracking_number' => $trackingNumber,
                    'status' => $status,
                    'date' => $date,
                    'raw_html' => null, // Can be useful for debugging
                ];
            }

            return [
                'tracking_number' => $trackingNumber,
                'status' => 'Status not found',
                'date' => null,
            ];

        } catch (Exception $e) {
            return [
                'tracking_number' => $trackingNumber,
                'status' => 'Error: ' . $e->getMessage(),
                'date' => null,
            ];
        }
    }
}
