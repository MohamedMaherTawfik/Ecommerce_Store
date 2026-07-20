<?php

return [
    'api_key' => env('EASYPOST_API_KEY'),
    'test_mode' => env('EASYPOST_TEST_MODE', true),
    'from' => [
        'name' => env('EASYPOST_FROM_NAME'),
        'company' => env('EASYPOST_FROM_COMPANY'),
        'phone' => env('EASYPOST_FROM_PHONE'),
        'email' => env('EASYPOST_FROM_EMAIL'),
        'street1' => env('EASYPOST_FROM_STREET1'),
        'street2' => env('EASYPOST_FROM_STREET2'),
        'city' => env('EASYPOST_FROM_CITY'),
        'state' => env('EASYPOST_FROM_STATE'),
        'zip' => env('EASYPOST_FROM_ZIP'),
        'country' => env('EASYPOST_FROM_COUNTRY'),
    ],
];
