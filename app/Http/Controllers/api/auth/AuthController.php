<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\concerns\authApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\loginRequest;
use App\Http\Resources\UserResource;
use App\Mail\OtpMail;
use App\Models\User;
use App\Support\Auth\AuthTokenCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    use authApiResponse;

    public function login(loginRequest $request)
    {
        $request->validated();

        try {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return $this->unauthorized('Invalid credentials.');
            }

            if (! $user->is_active) {
                return $this->forbidden('Account is disabled.');
            }

            $user->update(['last_seen' => now()]);

            $token = $user->createToken(
                'browser-session',
                ['*'],
                now()->addMinutes((int) config('auth_cookie.minutes'))
            )->plainTextToken;

            return $this->success([
                'user' => new UserResource($user),
            ], 'Logged in successfully.')->withCookie(AuthTokenCookie::make($token));
        } catch (\Exception $e) {
            \Log::error('User login failed with exception.', [
                'identity_hash' => hash('sha256', strtolower(trim((string) $request->email))),
                'exception' => $e::class,
            ]);

            return $this->error('Something went wrong during login. Please try again later.', 500);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorized('Unauthenticated.');
        }

        DB::transaction(function () use ($user) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->delete();
        });

        Auth::forgetGuards();

        $this->clearProfileCache($user->id);

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success([], 'Logged out successfully.')->withCookie(AuthTokenCookie::forget());
    }

    public function forgotPassword(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->success([], 'If an account exists, a reset code has been sent.');
        }

        $otp = random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($otp),
                'attempts' => 0,
                'locked_at' => null,
                'created_at' => now(),
            ]
        );

        Mail::to($user->email)->queue(new OtpMail($otp));

        return $this->success([], 'If an account exists, a reset code has been sent.');
    }

    public function resetPassword(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $request->validate([
            'email' => 'required|email',
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $reset = DB::transaction(function () use ($request): bool {
            $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                return false;
            }

            $expired = now()
                ->subMinutes((int) config('store.password_reset_otp_ttl'))
                ->greaterThan($record->created_at);
            $maximumAttempts = max(1, (int) config('store.password_reset_max_attempts', 5));

            if ($expired) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();

                return false;
            }

            if ($record->locked_at !== null || (int) $record->attempts >= $maximumAttempts) {
                return false;
            }

            if (! Hash::check($request->otp, $record->token)) {
                $attempts = (int) $record->attempts + 1;
                DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->update([
                        'attempts' => $attempts,
                        'locked_at' => $attempts >= $maximumAttempts ? now() : null,
                    ]);

                return false;
            }

            $user = User::query()->where('email', $request->email)->lockForUpdate()->first();

            if ($user !== null) {
                $user->update(['password' => Hash::make($request->password)]);
                $user->tokens()->delete();
            }

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return true;
        });

        if (! $reset) {
            return $this->error('Invalid or expired OTP.', 400);
        }

        return $this->success([], 'Password reset successfully.')
            ->withCookie(AuthTokenCookie::forget());
    }

    public function clearProfileCache($userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return;
        }

        Cache::forget("user_profile_{$userId}");
    }
}
