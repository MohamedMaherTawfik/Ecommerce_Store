<?php

return [
    'default' => 'paymob',

    'gateways' => [
        'paymob' => [
            'enabled' => env('PAYMOB_ENABLED', true),
            'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
            'public_key' => env('PAYMOB_PUBLIC_KEY'),
            'secret_key' => env('PAYMOB_SECRET_KEY'),
            'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
            'currency' => env('PAYMOB_CURRENCY', 'EGP'),
            'iframe_id' => env('PAYMOB_IFRAME_ID'),
            'integration_ids' => [
                'card' => env('PAYMOB_INTEGRATION_ID_CARD'),
                'mobile_wallet' => env('PAYMOB_INTEGRATION_ID_WALLET'),
                'apple_pay' => env('PAYMOB_INTEGRATION_ID_APPLE_PAY'),
            ],
        ],
    ],

    'channels' => [
        'card' => [
            'label' => 'Cards',
            'description' => 'Visa, Mastercard, and Meeza when enabled for your Paymob account.',
            'icon' => 'bi bi-credit-card-2-front',
        ],
        'apple_pay' => [
            'label' => 'Apple Pay',
            'description' => 'Available on supported Apple devices when enabled by Paymob.',
            'icon' => 'bi bi-apple',
        ],
        'mobile_wallet' => [
            'label' => 'Mobile Wallets',
            'description' => 'Vodafone Cash, Orange Cash, e& cash, WE Pay, and other enabled wallets.',
            'icon' => 'bi bi-phone',
        ],
    ],

    'urls' => [
        'callback' => env('PAYMOB_CALLBACK_URL'),
        'webhook' => env('PAYMOB_WEBHOOK_URL'),
        'success' => env('PAYMENT_SUCCESS_URL'),
        'failed' => env('PAYMENT_FAILED_URL'),
        'cancel' => env('PAYMENT_CANCEL_URL'),
    ],
];
