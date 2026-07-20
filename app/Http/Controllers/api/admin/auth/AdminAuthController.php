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

class AdminAuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        try {

            Log::info('Admin login request started', [
                'email' => $request->email,
            ]);

            $user = User::where('email', $request->email)->first();

            Log::info('User lookup result', [
                'exists' => (bool) $user,
                'user_id' => $user?->id,
                'role' => $user?->role,
            ]);

            if (! $user) {

                Log::warning('User not found', [
                    'email' => $request->email,
                ]);

                return $this->unauthorized('Invalid credentials.');
            }

            $passwordMatched = Hash::check($request->password, $user->password);

            Log::info('Password check result', [
                'matched' => $passwordMatched,
            ]);

            if (! $passwordMatched) {

                Log::warning('Password mismatch', [
                    'email' => $request->email,
                ]);

                return $this->unauthorized('Invalid credentials.');
            }

            if (! $user->canAccessAdmin()) {

                Log::warning('User without admin permissions tried to login', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                ]);

                return $this->unauthorized('You do not have dashboard access.');
            }

            if (! $user->is_active) {
                return $this->forbidden('Account is disabled.');
            }

            $user->update([
                'last_seen' => now(),
            ]);

            $token = $user->createToken('rag-token')->plainTextToken;

            Log::info('Admin logged in successfully', [
                'user_id' => $user->id,
            ]);

            return $this->success([
                'user' => new UserResource($user),
                'token' => $token,
            ], 'Logged in successfully.')->withCookie(AuthTokenCookie::make($token));

        } catch (\Throwable $e) {

            Log::error('Admin login failed with exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Something went wrong during login.',
                500
            );
        }
    }
}
