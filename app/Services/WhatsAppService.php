<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Parcel;
use App\Models\ParcelMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    private $baseUrl = "https://www.wasenderapi.com/api";

    /**
     * Format phone number for WhatsApp API (Algerian format)
     * Remove leading 0 and add +213
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace("/[^0-9+]/", "", $phoneNumber);

        // If it already has +213, return as is
        if (strpos($cleaned, "+213") === 0) {
            return $cleaned;
        }

        // If it starts with 213, add +
        if (strpos($cleaned, "213") === 0) {
            return "+" . $cleaned;
        }

        // If it starts with 0, remove it and add +213
        if (strpos($cleaned, "0") === 0) {
            return "+213" . substr($cleaned, 1);
        }

        // Otherwise, assume it's already without 0 and add +213
        return "+213" . $cleaned;
    }

    /**
     * Send a WhatsApp message using WasenderAPI
     */
    public function sendMessage(
        string $phoneNumber,
        string $message,
        Company $company,
    ): array {
        try {
            if (!$company->whatsapp_api_key) {
                throw new Exception(
                    "WhatsApp API key not configured for company: " .
                        $company->name,
                );
            }

            // Format phone number to international format (+213)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            $response = Http::timeout(60)
                ->withHeaders([
                    "Authorization" => "Bearer " . $company->whatsapp_api_key,
                    "Content-Type" => "application/json",
                ])
                ->post($this->baseUrl . "/send-message", [
                    "to" => $formattedPhone,
                    "text" => $message,
                ]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (!($responseData["success"] ?? true)) {
                    $errorMessage =
                        $responseData["message"] ?? "Unknown error from API";
                    Log::error(
                        "WhatsApp message failed (API reported failure)",
                        [
                            "phone" => $phoneNumber,
                            "company" => $company->name,
                            "error" => $errorMessage,
                            "response" => $responseData,
                        ],
                    );

                    $isDisconnected = str_contains(
                        $errorMessage,
                        "Your Whatsapp Session is not connected",
                    );

                    return [
                        "success" => false,
                        "error" => $errorMessage,
                        "status_code" => $response->status(),
                        "session_disconnected" => $isDisconnected,
                    ];
                }

                Log::info("WhatsApp message sent successfully", [
                    "original_phone" => $phoneNumber,
                    "formatted_phone" => $formattedPhone,
                    "company" => $company->name,
                    "response" => $responseData,
                ]);

                return [
                    "success" => true,
                    "message_id" => $responseData["message"]["id"] ?? null,
                    "response" => $responseData,
                ];
            } else {
                $errorMessage = $response->body();
                Log::error("WhatsApp message failed", [
                    "phone" => $phoneNumber,
                    "company" => $company->name,
                    "status" => $response->status(),
                    "error" => $errorMessage,
                ]);

                $isDisconnected = str_contains(
                    $errorMessage,
                    "Your Whatsapp Session is not connected",
                );

                return [
                    "success" => false,
                    "error" => $errorMessage,
                    "status_code" => $response->status(),
                    "session_disconnected" => $isDisconnected,
                ];
            }
        } catch (Exception $e) {
            Log::error("WhatsApp service error", [
                "phone" => $phoneNumber,
                "company" => $company->name,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Send message to parcel recipient and log it
     */
    public function sendMessageToParcel(
        Parcel $parcel,
        string $message,
        ?int $userId = null,
    ): array {
        $result = $this->sendMessage(
            $parcel->recipient_phone,
            $message,
            $parcel->company,
        );

        // Do not log if the session is disconnected, as it's a system issue, not a message failure.
        if (!($result["session_disconnected"] ?? false)) {
            ParcelMessage::create([
                "parcel_id" => $parcel->id,
                "user_id" => $userId,
                "message_type" => "outgoing",
                "message_status" => $result["success"] ? "sent" : "failed",
                "message_content" => $message,
                "phone_number" => $parcel->recipient_phone,
                "sent_at" => now(),
                "whatsapp_message_id" => $result["message_id"] ?? null,
                "error_message" => $result["error"] ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Send bulk messages to multiple parcels
     */
    public function sendBulkMessages(
        array $parcelIds,
        string $message,
        ?int $userId = null,
    ): array {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($parcelIds as $parcelId) {
            $parcel = Parcel::with("company")->find($parcelId);

            if (!$parcel) {
                $results[] = [
                    "parcel_id" => $parcelId,
                    "success" => false,
                    "error" => "Parcel not found",
                ];
                $failureCount++;
                continue;
            }

            $result = $this->sendMessageToParcel($parcel, $message, $userId);

            if ($result["session_disconnected"] ?? false) {
                return [
                    "success" => false,
                    "session_disconnected" => true,
                    "error" => "WhatsApp session is disconnected.",
                    "total" => count($parcelIds),
                    "success_count" => $successCount,
                    "failure_count" => $failureCount + 1,
                ];
            }

            $results[] = array_merge($result, ["parcel_id" => $parcelId]);

            if ($result["success"]) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            "total" => count($parcelIds),
            "success" => $successCount,
            "failed" => $failureCount,
            "results" => $results,
        ];
    }

    /**
     * Send delivery notification message
     */
    public function sendDeliveryNotification(
        Parcel $parcel,
        ?int $userId = null,
    ): array {
        $message = "Hello {$parcel->recipient_name}, your package with tracking number {$parcel->tracking_number} is ready for delivery. Please confirm your availability.";

        return $this->sendMessageToParcel($parcel, $message, $userId);
    }

    /**
     * Send collection notification message
     */
    public function sendCollectionNotification(
        Parcel $parcel,
        ?int $userId = null,
    ): array {
        $message = "Hello {$parcel->recipient_name}, your package with tracking number {$parcel->tracking_number} has been collected. COD amount: {$parcel->cod_amount} DA.";

        return $this->sendMessageToParcel($parcel, $message, $userId);
    }

    /**
     * Get message history for a parcel
     */
    public function getParcelMessageHistory(
        Parcel $parcel,
    ): \Illuminate\Database\Eloquent\Collection {
        return ParcelMessage::where("parcel_id", $parcel->id)
            ->with("user")
            ->orderBy("sent_at", "desc")
            ->get();
    }

    /**
     * Check if company has WhatsApp integration configured
     */
    public function isCompanyConfigured(Company $company): bool
    {
        return !empty($company->whatsapp_api_key);
    }

    /**
     * Test WhatsApp API connection for a company
     */
    public function testConnection(Company $company): array
    {
        try {
            $response = Http::withOptions([
                "timeout" => 20,
                "connect_timeout" => 15,
            ])
                ->withHeaders([
                    "Authorization" => "Bearer " . $company->whatsapp_api_key,
                    "Content-Type" => "application/json",
                ])
                ->get($this->baseUrl . "/status");

            if ($response->successful()) {
                return [
                    "success" => true,
                    "message" => "WhatsApp API connection successful",
                    "data" => $response->json(),
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "WhatsApp API connection failed",
                    "error" => $response->body(),
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "WhatsApp API connection error",
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve the current WhatsApp session status for a company.
     */
    public function getSessionStatus(Company $company): array
    {
        try {
            if (!$company->whatsapp_api_key) {
                throw new Exception(
                    "WhatsApp API key not configured for company: " .
                        $company->name,
                );
            }

            $response = Http::withOptions([
                "timeout" => 20,
                "connect_timeout" => 15,
            ])
                ->withHeaders([
                    "Authorization" => "Bearer " . $company->whatsapp_api_key,
                    "Content-Type" => "application/json",
                ])
                ->get($this->baseUrl . "/status");

            if ($response->successful()) {
                $data = $response->json();
                $status = strtoupper(
                    $data["status"] ?? ($data["data"]["status"] ?? "UNKNOWN"),
                );

                return [
                    "success" => true,
                    "status" => $status,
                    "raw" => $data,
                ];
            }

            $errorMessage = $response->json("message") ?? $response->body();
            Log::warning("Failed to fetch WhatsApp session status", [
                "company" => $company->name,
                "status_code" => $response->status(),
                "error" => $errorMessage,
            ]);

            return [
                "success" => false,
                "error" => $errorMessage,
                "status_code" => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error("WhatsApp session status error", [
                "company" => $company->name,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the WhatsApp session details for a company by matching phone numbers.
     * Implements retry logic with exponential backoff for better reliability.
     */
    private function getWhatsAppSession(
        Company $company,
        int $maxRetries = 2,
    ): ?array {
        // Allow more execution time for API calls with retries
        set_time_limit(60);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                $apiKey = config("services.wasender.api_key");
                if (!$apiKey) {
                    throw new Exception(
                        "Wasender API key (WSNAPI_KEY) is not configured.",
                    );
                }

                if (!$company->phone) {
                    throw new Exception(
                        "Company phone number is not configured.",
                    );
                }

                $response = Http::withOptions([
                    "timeout" => 20,
                    "connect_timeout" => 15,
                ])
                    ->withHeaders([
                        "Authorization" => "Bearer " . $apiKey,
                        "Content-Type" => "application/json",
                    ])
                    ->get($this->baseUrl . "/whatsapp-sessions");

                if ($response->successful()) {
                    $sessions = $response->json("data");
                    $companyPhone = $this->formatPhoneNumber($company->phone);

                    foreach ($sessions as $session) {
                        if (
                            isset($session["phone_number"]) &&
                            $this->formatPhoneNumber(
                                $session["phone_number"],
                            ) === $companyPhone
                        ) {
                            if ($attempt > 0) {
                                Log::info(
                                    "WhatsApp session retrieved after retry",
                                    [
                                        "company_id" => $company->id,
                                        "attempt" => $attempt + 1,
                                    ],
                                );
                            }
                            return $session;
                        }
                    }

                    return null; // No matching session found
                }

                Log::warning(
                    "Failed to fetch WhatsApp sessions from Wasender API (attempt " .
                        ($attempt + 1) .
                        ")",
                    [
                        "company_id" => $company->id,
                        "status" => $response->status(),
                        "error" => $response->body(),
                    ],
                );

                // Don't retry on 4xx errors (client errors)
                if ($response->status() >= 400 && $response->status() < 500) {
                    Log::error("Client error - not retrying", [
                        "company_id" => $company->id,
                        "status" => $response->status(),
                    ]);
                    return null;
                }
            } catch (Exception $e) {
                $lastException = $e;
                Log::warning(
                    "Error fetching WhatsApp session (attempt " .
                        ($attempt + 1) .
                        ")",
                    [
                        "company_id" => $company->id,
                        "error" => $e->getMessage(),
                    ],
                );
            }

            $attempt++;

            // Linear backoff: wait 0.5s between retries to stay under PHP max_execution_time
            if ($attempt < $maxRetries) {
                $waitTime = 0.5;
                Log::info(
                    "Retrying WhatsApp session fetch in {$waitTime} seconds",
                    [
                        "company_id" => $company->id,
                        "attempt" => $attempt + 1,
                        "max_retries" => $maxRetries,
                    ],
                );
                usleep($waitTime * 1000000); // Convert to microseconds
            }
        }

        // All retries exhausted
        Log::error("All retry attempts exhausted for WhatsApp session fetch", [
            "company_id" => $company->id,
            "attempts" => $maxRetries,
            "last_error" => $lastException
                ? $lastException->getMessage()
                : "Unknown error",
        ]);

        return null;
    }

    /**
     * Initiate (or re-initiate) a WhatsApp session connection and request a QR code.
     */
    public function connectSession(Company $company): array
    {
        try {
            // Allow more execution time for API calls
            set_time_limit(60);

            $session = $this->getWhatsAppSession($company);

            if (!$session) {
                throw new Exception(
                    "Could not find a matching WhatsApp session for company: " .
                        $company->name,
                );
            }

            $sessionId = $session["id"];
            $apiKey = config("services.wasender.api_key");

            if (!$apiKey) {
                throw new Exception(
                    "Wasender API key (WSNAPI_KEY) is not configured.",
                );
            }

            $response = Http::withOptions([
                "timeout" => 20,
                "connect_timeout" => 15,
            ])
                ->withHeaders([
                    "Authorization" => "Bearer " . $apiKey,
                    "Content-Type" => "application/json",
                ])
                ->post(
                    $this->baseUrl .
                        "/whatsapp-sessions/" .
                        $sessionId .
                        "/connect",
                );

            if ($response->successful()) {
                $data = $response->json();

                return [
                    "success" => true,
                    "status" => strtoupper(
                        $data["data"]["status"] ??
                            ($data["status"] ?? "UNKNOWN"),
                    ),
                    "qr_code" =>
                        $data["data"]["qrCode"] ?? ($data["qrCode"] ?? null),
                    "raw" => $data,
                ];
            }

            $errorMessage = $response->json("message") ?? $response->body();
            Log::warning("Failed to initiate WhatsApp session connection", [
                "company" => $company->name,
                "status_code" => $response->status(),
                "error" => $errorMessage,
            ]);

            return [
                "success" => false,
                "error" => $errorMessage,
                "status_code" => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error("WhatsApp session connection error", [
                "company" => $company->name,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve the latest QR code for a WhatsApp session.
     */
    public function getSessionQrCode(Company $company): array
    {
        try {
            // Allow more execution time for API calls
            set_time_limit(60);

            $session = $this->getWhatsAppSession($company);

            if (!$session) {
                throw new Exception(
                    "Could not find a matching WhatsApp session for company: " .
                        $company->name,
                );
            }

            $sessionId = $session["id"];
            $apiKey = config("services.wasender.api_key");

            if (!$apiKey) {
                throw new Exception(
                    "Wasender API key (WSNAPI_KEY) is not configured.",
                );
            }

            $response = Http::withOptions([
                "timeout" => 20,
                "connect_timeout" => 15,
            ])
                ->withHeaders([
                    "Authorization" => "Bearer " . $apiKey,
                    "Content-Type" => "application/json",
                ])
                ->get(
                    $this->baseUrl .
                        "/whatsapp-sessions/" .
                        $sessionId .
                        "/qrcode",
                );

            if ($response->successful()) {
                $data = $response->json();

                return [
                    "success" => true,
                    "qr_code" =>
                        $data["data"]["qrCode"] ?? ($data["qrCode"] ?? null),
                    "raw" => $data,
                ];
            }

            $errorMessage = $response->json("message") ?? $response->body();
            Log::warning("Failed to fetch WhatsApp session QR code", [
                "company" => $company->name,
                "status_code" => $response->status(),
                "error" => $errorMessage,
            ]);

            return [
                "success" => false,
                "error" => $errorMessage,
                "status_code" => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error("WhatsApp session QR code error", [
                "company" => $company->name,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Ensure a fresh QR code is available for reconnecting a WhatsApp session.
     */
    public function fetchSessionQrCode(Company $company): array
    {
        try {
            // Allow more execution time for API calls
            set_time_limit(60);

            if (!$company->whatsapp_api_key) {
                throw new Exception(
                    "WhatsApp API key not configured for company: " .
                        $company->name,
                );
            }

            $connectResult = $this->connectSession($company);
            $qrCode = $connectResult["qr_code"] ?? null;

            if (empty($qrCode)) {
                $qrResult = $this->getSessionQrCode($company);

                if ($qrResult["success"] && !empty($qrResult["qr_code"])) {
                    $qrCode = $qrResult["qr_code"];
                } else {
                    return [
                        "success" => false,
                        "error" =>
                            $qrResult["error"] ??
                            "Unable to retrieve WhatsApp session QR code",
                    ];
                }
            }

            return [
                "success" => true,
                "qr_code" => $qrCode,
                "status" => $connectResult["status"] ?? null,
            ];
        } catch (Exception $e) {
            Log::error("WhatsApp session fetch QR error", [
                "company" => $company->name,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Send desk pickup notification to parcel recipient
     * Uses company's customizable template with placeholders
     */
    public function sendDeskPickupNotification(Parcel $parcel): array
    {
        try {
            $company = $parcel->company;

            if (!$company->whatsapp_api_key) {
                throw new Exception(
                    "WhatsApp API key not configured for company: " .
                        $company->name,
                );
            }

            // Get template or use default
            $template =
                $company->whatsapp_desk_pickup_template ??
                $this->getDefaultDeskPickupTemplate();

            // Replace placeholders with actual data
            $message = $this->replacePlaceholders($template, $parcel);

            // Determine which phone to use (prefer recipient_phone, fallback to secondary)
            $phoneNumber = $parcel->recipient_phone ?? $parcel->secondary_phone;

            if (empty($phoneNumber)) {
                throw new Exception(
                    "No phone number available for parcel: " .
                        $parcel->tracking_number,
                );
            }

            // Send the message
            $result = $this->sendMessage($phoneNumber, $message, $company);

            // Store the message in history if successful
            if ($result["success"]) {
                ParcelMessage::create([
                    "parcel_id" => $parcel->id,
                    "user_id" => auth()->id(),
                    "message_type" => "outgoing",
                    "message_status" => "sent",
                    "message_content" => $message,
                    "phone_number" => $phoneNumber,
                    "sent_at" => now(),
                    "whatsapp_message_id" => $result["message_id"] ?? null,
                    "error_message" => null,
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Log::error("Desk pickup notification failed", [
                "parcel_id" => $parcel->id,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Get default desk pickup template
     */
    private function getDefaultDeskPickupTemplate(): string
    {
        return "📦 السلام عليكم،\n\n" .
            "نحن شركة التوصيل {company_name}، نعلمكم بوصول طلبكم.\n\n" .
            "📋 اسم المستلم: {recipient_name}\n" .
            "📦 المنتج: {parcel_designation}\n" .
            "💰 المبلغ: {parcel_amount} دج\n\n" .
            "يرجى التوجه إلى مكتبنا لاستلامه.\n\n" .
            "🔔 في حال كانت الطلبية موجهة للتوصيل إلى المنزل، يرجى ترك رسالة تحتوي على العنوان الكامل لتأكيد الإرسال.\n\n" .
            "📞 شكرًا لاختياركم {company_name}.\n\n" .
            "❗ ملاحظة: هذا آخر تواصل بخصوص طلبكم. يرجى الاستلام.";
    }

    /**
     * Replace template placeholders with actual parcel data
     */
    private function replacePlaceholders(
        string $template,
        Parcel $parcel,
    ): string {
        $replacements = [
            "{company_name}" => $parcel->company->name ?? "",
            "{recipient_name}" => $parcel->recipient_name ?? "",
            "{parcel_designation}" => $parcel->designation ?? "غير محدد",
            "{parcel_amount}" => number_format($parcel->cod_amount ?? 0, 2),
            "{tracking_number}" => $parcel->tracking_number ?? "",
            "{recipient_phone}" => $parcel->recipient_phone ?? "",
            "{recipient_address}" => $parcel->recipient_address ?? "",
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template,
        );
    }

    /**
     * Check if a phone number is on WhatsApp
     */
    public function checkPhoneOnWhatsApp(
        string $phoneNumber,
        Company $company,
    ): array {
        try {
            if (!$company->whatsapp_api_key) {
                throw new Exception(
                    "WhatsApp API key not configured for company: " .
                        $company->name,
                );
            }

            // Format phone number to international format (+213)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            $response = Http::withOptions([
                "timeout" => 20,
                "connect_timeout" => 15,
            ])
                ->withHeaders([
                    "Authorization" => "Bearer " . $company->whatsapp_api_key,
                    "Content-Type" => "application/json",
                ])
                ->get($this->baseUrl . "/on-whatsapp/" . $formattedPhone);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info("WhatsApp phone check successful", [
                    "original_phone" => $phoneNumber,
                    "formatted_phone" => $formattedPhone,
                    "company" => $company->name,
                    "response" => $responseData,
                ]);

                return [
                    "success" => true,
                    "exists" => $responseData["data"]["exists"] ?? false,
                    "response" => $responseData,
                ];
            } else {
                $errorMessage = $response->body();
                Log::error("WhatsApp phone check failed", [
                    "phone" => $phoneNumber,
                    "company" => $company->name,
                    "status" => $response->status(),
                    "error" => $errorMessage,
                ]);

                return [
                    "success" => false,
                    "exists" => false,
                    "error" => $errorMessage,
                    "status_code" => $response->status(),
                ];
            }
        } catch (Exception $e) {
            Log::error("WhatsApp phone check service error", [
                "phone" => $phoneNumber,
                "company" => $company->name,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "exists" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify WhatsApp phone numbers for a parcel
     */
    public function verifyParcelPhoneNumbers(Parcel $parcel): array
    {
        $results = [
            "recipient_phone_whatsapp" => null,
            "secondary_phone_whatsapp" => null,
            "verified_at" => now(),
            "errors" => [],
        ];

        // Check recipient phone
        if (!empty($parcel->recipient_phone)) {
            $recipientResult = $this->checkPhoneOnWhatsApp(
                $parcel->recipient_phone,
                $parcel->company,
            );
            $results["recipient_phone_whatsapp"] =
                $recipientResult["exists"] ?? false;

            if (!$recipientResult["success"]) {
                $results["errors"][] =
                    "Recipient phone check failed: " .
                    ($recipientResult["error"] ?? "Unknown error");
            }
        }

        // Check secondary phone
        if (!empty($parcel->secondary_phone)) {
            $secondaryResult = $this->checkPhoneOnWhatsApp(
                $parcel->secondary_phone,
                $parcel->company,
            );
            $results["secondary_phone_whatsapp"] =
                $secondaryResult["exists"] ?? false;

            if (!$secondaryResult["success"]) {
                $results["errors"][] =
                    "Secondary phone check failed: " .
                    ($secondaryResult["error"] ?? "Unknown error");
            }
        }

        // Update parcel with verification results
        $parcel->update([
            "recipient_phone_whatsapp" => $results["recipient_phone_whatsapp"],
            "secondary_phone_whatsapp" => $results["secondary_phone_whatsapp"],
            "whatsapp_verified_at" => $results["verified_at"],
        ]);

        return $results;
    }

    /**
     * Bulk verify WhatsApp phone numbers for multiple parcels
     */
    public function bulkVerifyPhoneNumbers(array $parcelIds): array
    {
        $results = [
            "total" => count($parcelIds),
            "verified" => 0,
            "failed" => 0,
            "errors" => [],
        ];

        foreach ($parcelIds as $parcelId) {
            $parcel = Parcel::with("company")->find($parcelId);

            if (!$parcel) {
                $results["errors"][] = "Parcel {$parcelId} not found";
                $results["failed"]++;
                continue;
            }

            try {
                $verificationResult = $this->verifyParcelPhoneNumbers($parcel);

                if (empty($verificationResult["errors"])) {
                    $results["verified"]++;
                } else {
                    $results["failed"]++;
                    $results["errors"] = array_merge(
                        $results["errors"],
                        $verificationResult["errors"],
                    );
                }
            } catch (Exception $e) {
                $results["failed"]++;
                $results["errors"][] =
                    "Parcel {$parcelId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Process incoming message from webhook
     */
    public function processIncomingMessage(array $webhookData): array
    {
        try {
            $messageId = $webhookData["data"]["key"]["id"];
            $senderPhone = $this->cleanPhoneNumber(
                $webhookData["data"]["key"]["remoteJid"],
            );
            $messageContent = $this->extractMessageContent(
                $webhookData["data"]["message"],
            );

            // Find parcel by phone number
            $parcel = $this->findParcelByPhoneNumber($senderPhone);

            if (!$parcel) {
                return [
                    "success" => false,
                    "error" => "No parcel found for phone number",
                    "phone" => $senderPhone,
                ];
            }

            // Check for duplicate message
            $existingMessage = ParcelMessage::where(
                "whatsapp_message_id",
                $messageId,
            )->first();
            if ($existingMessage) {
                return [
                    "success" => false,
                    "error" => "Duplicate message",
                    "message_id" => $messageId,
                ];
            }

            // Create message record
            $message = ParcelMessage::create([
                "parcel_id" => $parcel->id,
                "user_id" => null,
                "message_type" => "incoming",
                "message_status" => "received",
                "message_content" => $messageContent,
                "phone_number" => $senderPhone,
                "sent_at" => now(),
                "whatsapp_message_id" => $messageId,
                "error_message" => null,
            ]);

            return [
                "success" => true,
                "message_id" => $message->id,
                "parcel_id" => $parcel->id,
                "sender_phone" => $senderPhone,
            ];
        } catch (Exception $e) {
            Log::error("Error processing incoming message", [
                "error" => $e->getMessage(),
                "webhook_data" => $webhookData,
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean phone number for matching
     */
    private function cleanPhoneNumber(string $phoneNumber): string
    {
        $cleaned = preg_replace("/[^0-9+]/", "", $phoneNumber);
        $cleaned = preg_replace("/^\+?212/", "", $cleaned); // Morocco
        $cleaned = preg_replace("/^\+?1/", "", $cleaned); // US/Canada
        return $cleaned;
    }

    /**
     * Extract message content from different message types
     */
    private function extractMessageContent(array $message): string
    {
        if (isset($message["conversation"])) {
            return $message["conversation"];
        }

        if (isset($message["extendedTextMessage"]["text"])) {
            return $message["extendedTextMessage"]["text"];
        }

        if (isset($message["imageMessage"]["caption"])) {
            return "[Image] " . $message["imageMessage"]["caption"];
        }

        if (isset($message["videoMessage"]["caption"])) {
            return "[Video] " . $message["videoMessage"]["caption"];
        }

        if (isset($message["audioMessage"])) {
            return "[Audio Message]";
        }

        if (isset($message["documentMessage"]["fileName"])) {
            return "[Document] " . $message["documentMessage"]["fileName"];
        }

        if (isset($message["stickerMessage"])) {
            return "[Sticker]";
        }

        if (isset($message["locationMessage"])) {
            return "[Location]";
        }

        if (isset($message["contactMessage"])) {
            return "[Contact]";
        }

        return "[Unsupported Message Type]";
    }

    /**
     * Find parcel by phone number
     */
    private function findParcelByPhoneNumber(string $phoneNumber): ?Parcel
    {
        $cleanedPhone = $this->cleanPhoneNumber($phoneNumber);

        $parcel = Parcel::where(function ($query) use ($cleanedPhone) {
            $query
                ->whereRaw(
                    "REGEXP_REPLACE(recipient_phone, '[^0-9]', '') = ?",
                    [$cleanedPhone],
                )
                ->orWhereRaw(
                    "REGEXP_REPLACE(secondary_phone, '[^0-9]', '') = ?",
                    [$cleanedPhone],
                );
        })->first();

        return $parcel;
    }
}
