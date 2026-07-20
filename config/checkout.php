<?php

return [
    'currency' => env('CHECKOUT_CURRENCY', env('PAYPAL_CURRENCY', 'USD')),
    'tax_enabled' => env('TAX_ENABLED', true),
    'shipping_enabled' => env('SHIPPING_ENABLED', true),
    'flat_rate' => (float) env('DEFAULT_FLAT_SHIPPING_RATE', 10),
    'cod_confirms_order' => env('COD_CONFIRMS_ORDER', false),
    'stock_deduction_mode' => env('STOCK_DEDUCTION_MODE', 'order_placement'),
    'restore_stock_on_cancel' => env('RESTORE_STOCK_ON_CANCEL', true),
    'restore_stock_on_return' => env('RESTORE_STOCK_ON_RETURN', true),
    'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS')),
    'company' => [
        'name' => env('COMPANY_NAME', env('APP_NAME', 'Ecommerce Store')),
        'email' => env('COMPANY_EMAIL', env('MAIL_FROM_ADDRESS')),
        'phone' => env('COMPANY_PHONE'),
        'address' => env('COMPANY_ADDRESS'),
        'tax_number' => env('COMPANY_TAX_NUMBER'),
    ],
    'invoice' => [
        'prefix' => env('INVOICE_PREFIX', 'INV'),
        'next_number' => (int) env('INVOICE_NEXT_NUMBER', 1),
    ],
];
