<?php

return [
    'site_name' => env('SEO_SITE_NAME', env('APP_NAME', 'EliteShop')),
    'title_suffix' => env('SEO_TITLE_SUFFIX', env('APP_NAME', 'EliteShop')),
    'default_title' => env('SEO_DEFAULT_TITLE', 'Premium Products Online'),
    'default_description' => env(
        'SEO_DEFAULT_DESCRIPTION',
        'Discover quality products, trusted reviews, secure checkout, and fast delivery.'
    ),
    'default_image' => env('SEO_DEFAULT_IMAGE', '/images/logo.png'),
    'twitter_site' => env('SEO_TWITTER_SITE'),
    'supported_locales' => ['en', 'ar'],
    'default_locale' => env('APP_LOCALE', 'en'),
];
