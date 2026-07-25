<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove obsolete runtime settings while retaining historical payment rows.
     */
    public function up(): void
    {
        if (Schema::hasTable('payment_methods')) {
            DB::table('payment_methods')
                ->where('code', '!=', 'paymob')
                ->update([
                    'is_active' => false,
                    'is_default' => false,
                    'updated_at' => now(),
                ]);

            $paymobMethod = [
                'name' => 'Paymob Unified Checkout',
                'provider' => 'paymob',
                'is_active' => true,
                'is_default' => true,
                'mode' => app()->environment('production') ? 'live' : 'test',
                'settings' => json_encode(['channels' => ['card', 'apple_pay', 'mobile_wallet']]),
                'sort_order' => 1,
                'updated_at' => now(),
            ];

            if (DB::table('payment_methods')->where('code', 'paymob')->exists()) {
                DB::table('payment_methods')->where('code', 'paymob')->update($paymobMethod);
            } else {
                DB::table('payment_methods')->insert([
                    'code' => 'paymob',
                    ...$paymobMethod,
                    'credentials' => null,
                    'created_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('environment_settings')) {
            DB::table('environment_settings')
                ->where(function ($query): void {
                    foreach (['PAYPAL_', 'STRIPE_', 'MYFATOORAH_', 'BIONEER_'] as $prefix) {
                        $query->orWhere('key', 'like', $prefix.'%');
                    }
                })
                ->delete();
        }
    }

    /**
     * The previous gateway state is intentionally not reconstructed.
     */
    public function down(): void
    {
        //
    }
};
