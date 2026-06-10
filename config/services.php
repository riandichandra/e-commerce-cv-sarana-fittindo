<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'rajaongkir' => [
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
        'timeout' => (int) env('RAJAONGKIR_TIMEOUT', 8),
        'region_cache_ttl' => (int) env('RAJAONGKIR_REGION_CACHE_TTL', 604800),
        'origin_district_id' => env('RAJAONGKIR_ORIGIN_DISTRICT_ID'),
        'origin_label' => env('RAJAONGKIR_ORIGIN_LABEL', 'Gudang utama'),
        'default_couriers' => env('RAJAONGKIR_DEFAULT_COURIERS', 'jne:sicepat:jnt:tiki:pos'),
        'shipping_quote_ttl' => (int) env('RAJAONGKIR_SHIPPING_QUOTE_TTL', 600),
    ],

];
