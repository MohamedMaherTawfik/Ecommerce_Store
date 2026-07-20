<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    DB::table('password_reset_tokens')
        ->where('created_at', '<', now()->subMinutes(config('store.password_reset_otp_ttl')))
        ->delete();
})->hourly()->name('cleanup-expired-password-reset-tokens')->withoutOverlapping();
