<?php

require __DIR__ . "/vendor/autoload.php";

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

echo "=== WhatsApp QR Code Debug Script ===\n\n";

// Get API key from config
$apiKey = config("services.wasender.api_key");
$baseUrl = "https://www.wasenderapi.com/api";

if (!$apiKey) {
    echo "❌ ERROR: Wasender API key (WSNAPI_KEY) is not configured.\n";
    echo "Please set it in your .env file.\n";
    exit(1);
}

echo "✓ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";

// Get first company with whatsapp_api_key
$company = \App\Models\Company::whereNotNull("whatsapp_api_key")->first();

if (!$company) {
    echo "❌ ERROR: No company found with whatsapp_api_key configured.\n";
    exit(1);
}

echo "✓ Testing with company: {$company->name}\n";
echo "  Company ID: {$company->id}\n";
echo "  Company Phone: {$company->phone}\n\n";

// Step 1: Fetch all sessions
echo "--- Step 1: Fetching WhatsApp sessions ---\n";
try {
    $response = Http::withOptions([
        "timeout" => 20,
        "connect_timeout" => 15,
    ])
        ->withHeaders([
            "Authorization" => "Bearer " . $apiKey,
            "Content-Type" => "application/json",
        ])
        ->get($baseUrl . "/whatsapp-sessions");

    echo "Status Code: " . $response->status() . "\n";

    if ($response->successful()) {
        $data = $response->json();
        echo "✓ Successfully fetched sessions\n";
        echo "Raw response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

        $sessions = $data["data"] ?? [];
        echo "Found " . count($sessions) . " session(s)\n\n";

        foreach ($sessions as $index => $session) {
            echo "Session #" . ($index + 1) . ":\n";
            echo "  ID: " . ($session["id"] ?? "N/A") . "\n";
            echo "  Phone: " . ($session["phone_number"] ?? "N/A") . "\n";
            echo "  Status: " . ($session["status"] ?? "N/A") . "\n";
            echo "  Name: " . ($session["name"] ?? "N/A") . "\n\n";
        }

        // Find matching session
        $matchingSession = null;
        $companyPhone = preg_replace("/[^0-9]/", "", $company->phone);

        foreach ($sessions as $session) {
            $sessionPhone = preg_replace(
                "/[^0-9]/",
                "",
                $session["phone_number"] ?? "",
            );
            if ($sessionPhone === $companyPhone) {
                $matchingSession = $session;
                break;
            }
        }

        if ($matchingSession) {
            echo "✓ Found matching session for company phone\n";
            $sessionId = $matchingSession["id"];
            echo "  Session ID: {$sessionId}\n";
            echo "  Status: " . ($matchingSession["status"] ?? "N/A") . "\n\n";

            // Step 2: Try to connect session
            echo "--- Step 2: Connecting session ---\n";
            $connectResponse = Http::withOptions([
                "timeout" => 20,
                "connect_timeout" => 15,
            ])
                ->withHeaders([
                    "Authorization" => "Bearer " . $apiKey,
                    "Content-Type" => "application/json",
                ])
                ->post(
                    $baseUrl . "/whatsapp-sessions/" . $sessionId . "/connect",
                );

            echo "Status Code: " . $connectResponse->status() . "\n";
            $connectData = $connectResponse->json();
            echo "Connect response:\n";
            echo json_encode($connectData, JSON_PRETTY_PRINT) . "\n\n";

            if ($connectResponse->successful()) {
                $qrCode =
                    $connectData["data"]["qrCode"] ??
                    ($connectData["qrCode"] ?? null);

                if ($qrCode) {
                    echo "✓ QR Code received from connect endpoint!\n";
                    echo "  QR Code length: " .
                        strlen($qrCode) .
                        " characters\n";
                    echo "  First 50 chars: " .
                        substr($qrCode, 0, 50) .
                        "...\n\n";
                } else {
                    echo "⚠ No QR code in connect response\n";
                    echo "  This might mean the session is already connected\n\n";
                }
            } else {
                echo "❌ Connect request failed\n";
                echo "  Error: " .
                    ($connectData["message"] ?? "Unknown error") .
                    "\n\n";
            }

            // Step 3: Try to get QR code directly
            echo "--- Step 3: Fetching QR code directly ---\n";
            $qrResponse = Http::withOptions([
                "timeout" => 20,
                "connect_timeout" => 15,
            ])
                ->withHeaders([
                    "Authorization" => "Bearer " . $apiKey,
                    "Content-Type" => "application/json",
                ])
                ->get(
                    $baseUrl . "/whatsapp-sessions/" . $sessionId . "/qrcode",
                );

            echo "Status Code: " . $qrResponse->status() . "\n";
            $qrData = $qrResponse->json();
            echo "QR code response:\n";
            echo json_encode($qrData, JSON_PRETTY_PRINT) . "\n\n";

            if ($qrResponse->successful()) {
                $qrCode =
                    $qrData["data"]["qrCode"] ?? ($qrData["qrCode"] ?? null);

                if ($qrCode) {
                    echo "✓ QR Code received!\n";
                    echo "  QR Code length: " .
                        strlen($qrCode) .
                        " characters\n";
                    echo "  First 50 chars: " .
                        substr($qrCode, 0, 50) .
                        "...\n\n";
                } else {
                    echo "⚠ No QR code in response\n";
                    echo "  Check if the session needs reconnection\n\n";
                }
            } else {
                echo "❌ QR code request failed\n";
                echo "  Error: " .
                    ($qrData["message"] ?? "Unknown error") .
                    "\n\n";
            }

            // Step 4: Check session status
            echo "--- Step 4: Checking session status ---\n";
            $statusResponse = Http::withOptions([
                "timeout" => 20,
                "connect_timeout" => 15,
            ])
                ->withHeaders([
                    "Authorization" => "Bearer " . $apiKey,
                    "Content-Type" => "application/json",
                ])
                ->get(
                    $baseUrl . "/whatsapp-sessions/" . $sessionId . "/status",
                );

            echo "Status Code: " . $statusResponse->status() . "\n";
            $statusData = $statusResponse->json();
            echo "Status response:\n";
            echo json_encode($statusData, JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "❌ No matching session found for company phone: {$company->phone}\n";
            echo "   Formatted: {$companyPhone}\n";
            echo "   Please create a session on Wasender dashboard first.\n";
        }
    } else {
        echo "❌ Failed to fetch sessions\n";
        echo "Response: " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug script completed ===\n";
