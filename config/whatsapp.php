<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp webhook integration with wasenderapi.com
    |
    */

    'webhook' => [
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'your_verification_token'),
        'secret' => env('WHATSAPP_WEBHOOK_SECRET', 'your_webhook_secret'),
    ],

    'api' => [
        'base_url' => 'https://www.wasenderapi.com/api',
        'timeout' => 30,
    ],

    'message_types' => [
        'conversation',
        'extendedTextMessage',
        'imageMessage',
        'videoMessage',
        'audioMessage',
        'documentMessage',
        'stickerMessage',
        'locationMessage',
        'contactMessage',
    ],

    'phone_cleaning' => [
        'remove_patterns' => [
            '/[^0-9+]/',
            '/^\+?212/', // Morocco
            '/^\+?1/', // US/Canada
        ],
        'country_codes' => [
            'morocco' => '212',
            'us_canada' => '1',
        ],
    ],
];
