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
        $frontend = config('app.frontend_url');
        $appUrl = config('app.url');

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
                    .'/auth/google-error?message='
                    .urlencode('Google account did not return an email address.')
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
                    .'/auth/google-error?message='
                    .urlencode('This account is currently disabled.')
                );
            }

            $token = $user->createToken(
                'browser-session',
                ['*'],
                now()->addMinutes((int) config('auth_cookie.minutes'))
            )->plainTextToken;

            return redirect()->away($this->frontendBaseUrl().'/auth/google-success')
                ->withCookie(AuthTokenCookie::make($token));
        } catch (\Exception $e) {
            return redirect()->away(
                $this->frontendBaseUrl()
                .'/auth/google-error?message='
                .urlencode('Google login could not be completed.')
            );
        }
    }
}
