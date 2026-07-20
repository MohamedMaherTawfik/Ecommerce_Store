<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_is_non_enumerable_and_expired_codes_are_rejected(): void
    {
        Mail::fake();
        $this->api()->postJson('/api/v1/users/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists, a reset code has been sent.');

        $user = User::factory()->create(['email' => 'buyer@example.com']);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(11),
        ]);

        $this->api()->postJson('/api/v1/users/reset-password', [
            'email' => $user->email,
            'otp' => '123456',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])->assertStatus(400);

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }
}
