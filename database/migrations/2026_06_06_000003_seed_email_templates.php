<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            ['key' => 'welcome', 'name' => 'Welcome Email', 'subject' => 'Welcome to {{ app_name }}', 'html_body' => '<h1>Welcome, {{ user_name }}!</h1><p>Your account {{ user_email }} is ready.</p><p><a href="{{ app_url }}">Visit the store</a></p>', 'variables' => ['user_name', 'user_email', 'app_name', 'app_url']],
            ['key' => 'order_confirmation', 'name' => 'Order Confirmation', 'subject' => 'Order {{ order_number }} confirmed', 'html_body' => '<h1>Thank you, {{ user_name }}</h1><p>Order <strong>{{ order_number }}</strong> has been placed.</p><p>Total: {{ currency }} {{ total }}</p>', 'variables' => ['user_name', 'order_number', 'currency', 'total']],
            ['key' => 'order_shipped', 'name' => 'Order Shipped', 'subject' => 'Order {{ order_number }} shipped', 'html_body' => '<h1>Your order is on its way</h1><p>Order {{ order_number }} has shipped.</p><p>Tracking: {{ tracking_number }}</p>', 'variables' => ['order_number', 'tracking_number']],
            ['key' => 'order_delivered', 'name' => 'Order Delivered', 'subject' => 'Order {{ order_number }} delivered', 'html_body' => '<h1>Order delivered</h1><p>Order {{ order_number }} has been marked as delivered.</p>', 'variables' => ['order_number']],
            ['key' => 'payment_success', 'name' => 'Payment Success', 'subject' => 'Payment received for {{ order_number }}', 'html_body' => '<h1>Payment successful</h1><p>We received {{ currency }} {{ total }} for order {{ order_number }}.</p>', 'variables' => ['order_number', 'currency', 'total', 'user_name']],
            ['key' => 'payment_failed', 'name' => 'Payment Failed', 'subject' => 'Payment failed', 'html_body' => '<h1>Payment could not be completed</h1><p>Hello {{ user_name }}, the attempted payment of {{ currency }} {{ total }} failed.</p>', 'variables' => ['user_name', 'currency', 'total']],
            ['key' => 'password_reset', 'name' => 'Password Reset', 'subject' => 'Your password reset code', 'html_body' => '<h1>Password reset</h1><p>Your verification code is:</p><h2>{{ otp }}</h2><p>This code expires in {{ expires_minutes }} minutes.</p>', 'variables' => ['otp', 'expires_minutes']],
        ];

        $now = now();
        foreach ($templates as $template) {
            DB::table('email_templates')->updateOrInsert(
                ['key' => $template['key']],
                array_merge($template, [
                    'variables' => json_encode($template['variables']),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->whereIn('key', [
            'welcome', 'order_confirmation', 'order_shipped', 'order_delivered',
            'payment_success', 'payment_failed', 'password_reset',
        ])->delete();
    }
};
