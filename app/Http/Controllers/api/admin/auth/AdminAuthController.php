<?php

namespace App\Http\Controllers\api\admin\auth;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Auth\AuthTokenCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        try {
            $user = User::where('email', $credentials['email'])->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                Log::warning('Admin login rejected', [
                    'identity_hash' => hash('sha256', $credentials['email']),
                    'ip' => $request->ip(),
                ]);

                return $this->unauthorized('Invalid credentials.');
            }

            if (! $user->canAccessAdmin()) {

                Log::warning('User without admin permissions tried to login', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                ]);

                return $this->unauthorized('Invalid credentials.');
            }

            if (! $user->is_active) {
                return $this->forbidden('Account is disabled.');
            }

            $user->update([
                'last_seen' => now(),
            ]);

            $token = $user->createToken(
                'browser-session',
                ['*'],
                now()->addMinutes((int) config('auth_cookie.minutes'))
            )->plainTextToken;

            Log::info('Admin logged in successfully', [
                'user_id' => $user->id,
            ]);

            return $this->success([
                'user' => new UserResource($user),
            ], 'Logged in successfully.')->withCookie(AuthTokenCookie::make($token));

        } catch (\Throwable $e) {

            Log::error('Admin login failed with exception', [
                'exception' => $e::class,
                'request_id' => (string) Str::uuid(),
            ]);

            return $this->error(
                'Something went wrong during login.',
                500
            );
        }
    }
}
