<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Requests\Admin\SiteSettingRequest;
use App\Services\Media\OptimizedImageStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiteSettingController extends Controller
{
    use ApiResponse;

    private $cacheTime = 3600;

    // =========================
    // INDEX (CRITICAL - GLOBAL CACHE)
    // =========================
    public function index()
    {
        try {
            $settings = \App\Support\TaggedCache::tags(['settings'])->remember(
                'site_settings_all',
                $this->cacheTime,
                function () {
                    $items = SiteSetting::all();

                    $result = [];
                    foreach ($items as $item) {
                        $val = $item->value;
                        if (is_string($val) && (str_starts_with($val, 'storage/') || str_starts_with($val, '/storage/'))) {
                            $val = asset(ltrim($val, '/'));
                        }
                        $result[$item->key] = $val;
                    }

                    return $result;
                }
            );

            return $this->success($settings, 'Site settings retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // SHOW
    // =========================
    public function show($key)
    {
        try {
            $setting = \App\Support\TaggedCache::tags(['settings'])->remember(
                "setting_$key",
                $this->cacheTime,
                fn() => SiteSetting::where('key', $key)->first()
            );

            if (!$setting) {
                return $this->notFound('Site setting not found');
            }

            $val = $setting->value;
            if (is_string($val) && (str_starts_with($val, 'storage/') || str_starts_with($val, '/storage/'))) {
                $setting->value = asset(ltrim($val, '/'));
            }

            return $this->success($setting, 'Site setting retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // STORE
    // =========================
    public function store(SiteSettingRequest $request, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            if ($request->hasFile('value')) {
                $file = $request->file('value');
                $path = $imageStorage->store($file, 'settings', 1200, 1200);
                $data['value'] = '/storage/' . $path;
            }

            $setting = SiteSetting::create($data);

            \App\Support\TaggedCache::tags(['settings'])->flush();

            DB::commit();

            return $this->success($setting, 'Site setting created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // UPDATE (UPSERT STYLE)
    // =========================
    public function update(SiteSettingRequest $request, $key, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            $setting = SiteSetting::firstOrNew(['key' => $key]);

            if ($request->hasFile('value')) {
                $file = $request->file('value');
                $path = $imageStorage->store($file, 'settings', 1200, 1200);
                $setting->value = '/storage/' . $path;
            } else {
                $setting->value = $request->validated()['value'];
            }
            $setting->save();

            \App\Support\TaggedCache::tags(['settings'])->flush();

            DB::commit();

            return $this->success($setting, 'Site setting updated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // DELETE
    // =========================
    public function destroy($key)
    {
        try {
            DB::beginTransaction();

            $setting = SiteSetting::where('key', $key)->first();

            if (!$setting) {
                return $this->notFound('Site setting not found');
            }

            $setting->delete();

            \App\Support\TaggedCache::tags(['settings'])->flush();

            DB::commit();

            return $this->success([], 'Site setting deleted successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // BATCH UPDATE
    // =========================
    public function batchUpdate(\Illuminate\Http\Request $request, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            foreach ($request->all() as $key => $value) {
                if (in_array($key, ['_token', '_method'], true) || ! preg_match('/^[A-Za-z0-9_.-]{1,100}$/', (string) $key)) {
                    continue;
                }

                if ($request->hasFile($key)) {
                    $request->validate([
                        $key => ['image', 'mimes:jpg,jpeg,png,webp,ico', 'max:2048'],
                    ]);
                    $file = $request->file($key);
                    $path = $imageStorage->store($file, 'settings', 1200, 1200);
                    $value = '/storage/' . $path;
                }

                if ($value !== null && ! is_array($value)) {
                    SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
                }
            }

            \App\Support\TaggedCache::tags(['settings'])->flush();

            DB::commit();

            return $this->success([], 'Settings updated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }
}


