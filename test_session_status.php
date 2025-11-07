<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Session Status Endpoint ===\n\n";

// Get first company with whatsapp_api_key
$company = \App\Models\Company::whereNotNull('whatsapp_api_key')->first();

if (!$company) {
    echo "❌ ERROR: No company found with whatsapp_api_key configured.\n";
    exit(1);
}

echo "✓ Testing with company: {$company->name}\n";
echo "  Company ID: {$company->id}\n";
echo "  API Key configured: " . ($company->whatsapp_api_key ? "Yes" : "No") . "\n\n";

// Create an instance of WhatsAppService
$whatsappService = new \App\Services\WhatsAppService();

echo "--- Step 1: Getting Session Status ---\n";
try {
    $statusResult = $whatsappService->getSessionStatus($company);
    echo "Status Result:\n";
    echo json_encode($statusResult, JSON_PRETTY_PRINT) . "\n\n";

    if ($statusResult['success']) {
        $status = $statusResult['status'] ?? 'UNKNOWN';
        $needsQr = !in_array($status, ['CONNECTED', 'WORKING']);

        echo "Status: {$status}\n";
        echo "Needs QR: " . ($needsQr ? "Yes" : "No") . "\n\n";

        if ($needsQr) {
            echo "--- Step 2: Fetching QR Code ---\n";
            $qrResult = $whatsappService->fetchSessionQrCode($company);
            echo "QR Result:\n";
            echo json_encode($qrResult, JSON_PRETTY_PRINT) . "\n\n";

            if ($qrResult['success'] && isset($qrResult['qr_code'])) {
                $qrCode = $qrResult['qr_code'];
                echo "✓ QR Code retrieved!\n";
                echo "  Length: " . strlen($qrCode) . " characters\n";
                echo "  First 50 chars: " . substr($qrCode, 0, 50) . "...\n";
                echo "  Is data URL: " . (strpos($qrCode, 'data:') === 0 ? "Yes" : "No") . "\n\n";

                // Simulate what the controller returns
                echo "--- Step 3: Simulating Controller Response ---\n";
                $controllerResponse = [
                    'success' => true,
                    'data' => [
                        'status' => $status,
                        'needs_qr' => $needsQr,
                        'qr_code' => $qrCode,
                        'qr_error' => null,
                        'checked_at' => now()->toISOString(),
                    ],
                ];

                echo "Controller would return:\n";
                echo json_encode($controllerResponse, JSON_PRETTY_PRINT) . "\n\n";

                echo "✅ QR Code is available and should display on frontend\n";
            } else {
                echo "❌ Failed to fetch QR code\n";
                echo "Error: " . ($qrResult['error'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "ℹ️  Session is connected - no QR code needed\n";
        }
    } else {
        echo "❌ Failed to get session status\n";
        echo "Error: " . ($statusResult['error'] ?? 'Unknown error') . "\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
