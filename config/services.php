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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'redirect_url' => env('GOOGLE_REDIRECT_URL', env('GOOGLE_REDIRECT_URI')),
    ],

    'api' => [
        'key' => env('API_KEY'),
    ],

    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', false),
        'public_key' => env('STRIPE_PUBLIC_KEY', env('STRIPE_KEY')),
        'secret_key' => env('STRIPE_SECRET_KEY', env('STRIPE_SECRET')),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
    ],

    'paymob' => [
        'enabled' => env('PAYMOB_ENABLED', false),
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'integration_id' => env('PAYMOB_INTEGRATION_ID'),
        'currency' => env('PAYMOB_CURRENCY', 'EGP'),
    ],

    'myfatoorah' => [
        'enabled' => env('MYFATOORAH_ENABLED', false),
        'api_key' => env('MYFATOORAH_API_KEY'),
        'base_url' => env('MYFATOORAH_BASE_URL', 'https://apitest.myfatoorah.com'),
        'country_iso' => env('MYFATOORAH_COUNTRY_ISO', 'KWT'),
        'currency' => env('MYFATOORAH_CURRENCY', 'KWD'),
        'webhook_secret' => env('MYFATOORAH_WEBHOOK_SECRET'),
    ],

    'payment_urls' => [
        'success' => env('PAYMENT_SUCCESS_URL'),
        'failed' => env('PAYMENT_FAILED_URL'),
        'cancel' => env('PAYMENT_CANCEL_URL'),
    ],

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

];
