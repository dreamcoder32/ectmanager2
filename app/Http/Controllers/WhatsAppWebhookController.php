<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\ParcelMessage;
use App\Models\Company;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppWebhookController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle incoming WhatsApp messages from wasenderapi.com webhook
     */
    public function handleIncomingMessage(Request $request)
    {
        try {
            // Log the incoming webhook for debugging
            Log::info('WhatsApp webhook received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate the webhook payload
            $validator = Validator::make($request->all(), [
                'event' => 'required|string',
                'timestamp' => 'required|integer',
                'data' => 'required|array',
                'data.key' => 'required|array',
                'data.key.id' => 'required|string',
                'data.key.fromMe' => 'required|boolean',
                'data.key.remoteJid' => 'required|string',
                'data.message' => 'required|array'
            ]);

            if ($validator->fails()) {
                Log::error('WhatsApp webhook validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'payload' => $request->all()
                ]);
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            $payload = $request->all();

            // Only process incoming messages (not outgoing)
            if ($payload['data']['key']['fromMe'] === true) {
                Log::info('Skipping outgoing message', [
                    'message_id' => $payload['data']['key']['id']
                ]);
                return response()->json(['status' => 'skipped'], 200);
            }

            // Extract message data
            $messageId = $payload['data']['key']['id'];
            $senderPhone = $this->cleanPhoneNumber($payload['data']['key']['remoteJid']);
            $messageContent = $this->extractMessageContent($payload['data']['message']);
            $timestamp = $payload['timestamp'];

            if (empty($messageContent)) {
                Log::warning('Empty message content received', [
                    'message_id' => $messageId,
                    'sender_phone' => $senderPhone
                ]);
                return response()->json(['status' => 'empty_message'], 200);
            }

            // Find the parcel associated with this phone number
            $parcel = $this->findParcelByPhoneNumber($senderPhone);

            if (!$parcel) {
                Log::warning('No parcel found for incoming message', [
                    'sender_phone' => $senderPhone,
                    'message_id' => $messageId
                ]);
                return response()->json(['status' => 'no_parcel_found'], 200);
            }

            // Check if message already exists (prevent duplicates)
            $existingMessage = ParcelMessage::where('whatsapp_message_id', $messageId)->first();
            if ($existingMessage) {
                Log::info('Duplicate message received, skipping', [
                    'message_id' => $messageId,
                    'parcel_id' => $parcel->id
                ]);
                return response()->json(['status' => 'duplicate'], 200);
            }

            // Create the incoming message record
            $message = ParcelMessage::create([
                'parcel_id' => $parcel->id,
                'user_id' => null, // System message
                'message_type' => 'incoming',
                'message_status' => 'received',
                'message_content' => $messageContent,
                'phone_number' => $senderPhone,
                'sent_at' => now(),
                'whatsapp_message_id' => $messageId,
                'error_message' => null,
            ]);

            Log::info('Incoming WhatsApp message stored', [
                'message_id' => $message->id,
                'parcel_id' => $parcel->id,
                'sender_phone' => $senderPhone,
                'whatsapp_message_id' => $messageId
            ]);

            return response()->json([
                'status' => 'success',
                'message_id' => $message->id,
                'parcel_id' => $parcel->id
            ], 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Clean phone number for matching
     */
    private function cleanPhoneNumber(string $phoneNumber): string
    {
        // Remove common prefixes and clean the number
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Remove common country code prefixes
        $cleaned = preg_replace('/^\+?212/', '', $cleaned); // Morocco
        $cleaned = preg_replace('/^\+?1/', '', $cleaned); // US/Canada
        
        return $cleaned;
    }

    /**
     * Extract message content from different message types
     */
    private function extractMessageContent(array $message): string
    {
        // Handle different message types
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }
        
        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }
        
        if (isset($message['imageMessage']['caption'])) {
            return '[Image] ' . $message['imageMessage']['caption'];
        }
        
        if (isset($message['videoMessage']['caption'])) {
            return '[Video] ' . $message['videoMessage']['caption'];
        }
        
        if (isset($message['audioMessage'])) {
            return '[Audio Message]';
        }
        
        if (isset($message['documentMessage']['fileName'])) {
            return '[Document] ' . $message['documentMessage']['fileName'];
        }
        
        if (isset($message['stickerMessage'])) {
            return '[Sticker]';
        }
        
        if (isset($message['locationMessage'])) {
            return '[Location]';
        }
        
        if (isset($message['contactMessage'])) {
            return '[Contact]';
        }
        
        // Fallback for unknown message types
        return '[Unsupported Message Type]';
    }

    /**
     * Find parcel by phone number (check both recipient and secondary phones)
     */
    private function findParcelByPhoneNumber(string $phoneNumber): ?Parcel
    {
        $cleanedPhone = $this->cleanPhoneNumber($phoneNumber);
        
        // First try to find by recipient phone
        $parcel = Parcel::where(function ($query) use ($cleanedPhone) {
            $query->whereRaw("REGEXP_REPLACE(recipient_phone, '[^0-9]', '') = ?", [$cleanedPhone])
                  ->orWhereRaw("REGEXP_REPLACE(secondary_phone, '[^0-9]', '') = ?", [$cleanedPhone]);
        })->first();

        if ($parcel) {
            return $parcel;
        }

        // If not found, try with different phone number formats
        $variations = $this->generatePhoneVariations($cleanedPhone);
        
        foreach ($variations as $variation) {
            $parcel = Parcel::where(function ($query) use ($variation) {
                $query->whereRaw("REGEXP_REPLACE(recipient_phone, '[^0-9]', '') = ?", [$variation])
                      ->orWhereRaw("REGEXP_REPLACE(secondary_phone, '[^0-9]', '') = ?", [$variation]);
            })->first();

            if ($parcel) {
                return $parcel;
            }
        }

        return null;
    }

    /**
     * Generate different phone number variations for matching
     */
    private function generatePhoneVariations(string $phoneNumber): array
    {
        $variations = [];
        
        // Add country code variations
        if (strlen($phoneNumber) === 9) {
            $variations[] = '212' . $phoneNumber; // Morocco
            $variations[] = '+212' . $phoneNumber;
        }
        
        if (strlen($phoneNumber) === 10) {
            $variations[] = '1' . $phoneNumber; // US/Canada
            $variations[] = '+1' . $phoneNumber;
        }
        
        // Add with leading zeros
        if (!str_starts_with($phoneNumber, '0')) {
            $variations[] = '0' . $phoneNumber;
        }
        
        return array_unique($variations);
    }

    /**
     * Handle webhook verification (if required by wasenderapi.com)
     */
    public function verifyWebhook(Request $request)
    {
        // This method can be used for webhook verification if required
        $challenge = $request->get('hub_challenge');
        $verifyToken = $request->get('hub_verify_token');
        
        // You can set a verification token in your environment
        $expectedToken = config('whatsapp.webhook_verify_token', 'your_verification_token');
        
        if ($verifyToken === $expectedToken) {
            return response($challenge, 200);
        }
        
        return response('Forbidden', 403);
    }

    /**
     * Get webhook status and recent messages for debugging
     */
    public function getWebhookStatus()
    {
        $recentMessages = ParcelMessage::where('message_type', 'incoming')
            ->with('parcel')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'active',
            'recent_messages' => $recentMessages,
            'total_incoming_messages' => ParcelMessage::where('message_type', 'incoming')->count()
        ]);
    }
}