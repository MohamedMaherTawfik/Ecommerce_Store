<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Throwable;

class ProfileController extends Controller
{
    use ApiResponse;

    public function profile()
    {
        try {
            $user = auth()->user();

            if (! $user) {
                Log::warning('Profile fetch failed: Unauthenticated.', [
                    'headers' => request()->headers->all(),
                ]);

                return $this->unauthorized('Unauthenticated.');
            }

            Log::info('Profile fetch successful for user: '.$user->id);

            $cacheKey = "user_profile_{$user->id}";

            $profile = Cache::remember($cacheKey, 600, function () use ($user) {
                return new UserResource($user);
            });

            return $this->success(
                ['user' => $profile],
                'Profile fetched successfully.'
            );

        } catch (Throwable $e) {
            report($e);

            return $this->error(
                'Something went wrong while fetching profile.',
                500
            );
        }
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone' => 'nullable|numeric',
        ]);

        if ($user->email !== $validated['email']) {
            $validated['email_verified_at'] = null;
        }

        $user->update($validated);

        if ($request->hasFile('image')) {
            if ($user->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->image);
            }
            $user->image = $request->image->store('users', 'public');
            $user->save();
        }
        $this->clearProfileCache($user->id);

        return $this->success([
            'user' => new UserResource($user),
        ], 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()],
            'confirm_password' => 'required|string|same:new_password',
        ]);
        if (! Hash::check($validated['current_password'], $user->password)) {
            return $this->forbidden('Current password is incorrect.');
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return $this->success([], 'Password updated successfully.');
    }

    public function deleteAccount()
    {
        try {
            $user = auth()->user();
            $user->update(['is_active' => false]);
            $this->clearProfileCache($user->id);
            $user->tokens()->delete();
            $user->delete();

            return $this->success([], 'Account deleted successfully.');

        } catch (Throwable $e) {
            return $this->error('Something went wrong while deleting account.', 500);
        }
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
