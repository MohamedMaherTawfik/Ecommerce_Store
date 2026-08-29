<?php

return [
    'password_reset_otp_ttl' => (int) env('PASSWORD_RESET_OTP_TTL', 10),
    'password_reset_max_attempts' => (int) env('PASSWORD_RESET_MAX_ATTEMPTS', 5),
    'analytics_cache_minutes' => (int) env('ANALYTICS_CACHE_MINUTES', 5),
];
