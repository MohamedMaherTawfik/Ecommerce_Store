<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\AuthTokenCookie;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private function frontendBaseUrl(): string
    {
        $frontend = env('FRONTEND_URL');
        $appUrl = env('APP_URL', config('app.url'));

        return rtrim((string) ($frontend ?: $appUrl), '/');
    }

    public function googleLogin()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function googleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            $email = $googleUser->getEmail();

            if (! $email) {
                return redirect()->away(
                    $this->frontendBaseUrl()
                    . '/auth/google-error?message='
                    . urlencode('Google account did not return an email address.')
                );
            }

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $googleUser->getName() ?: 'Google User',
                    'role' => 'user',
                    'is_active' => true,
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => Str::random(24),
                    'last_seen' => now(),
                ]
            );

            if (! $user->is_active) {
                return redirect()->away(
                    $this->frontendBaseUrl()
                    . '/auth/google-error?message='
                    . urlencode('This account is currently disabled.')
                );
            }

            // Issue a Sanctum token and pass it in the redirect URL.
            // The SPA reads it from the query string, stores it, and strips the URL.
            $token = $user->createToken('google-auth-token')->plainTextToken;

            return redirect()->away(
                $this->frontendBaseUrl()
                . '/auth/google-success?token='
                . urlencode($token)
                . '&role='
                . urlencode(Str::lower((string) $user->role))
            );
        } catch (\Exception $e) {
            return redirect()->away(
                $this->frontendBaseUrl()
                . '/auth/google-error?message='
                . urlencode($e->getMessage())
            );
        }
    }
}
