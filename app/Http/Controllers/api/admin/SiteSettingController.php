<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\Media\OptimizedImageStorage;
use App\Support\TaggedCache;
use Illuminate\Http\Request;
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
            $settings = TaggedCache::tags(['settings'])->remember(
                'site_settings_all',
                $this->cacheTime,
                function () {
                    $items = SiteSetting::all();

                    $result = [];
                    $imageKeys = ['navbar_image', 'footer_image', 'register_image', 'tab_icon', 'navbar_logo'];
                    foreach ($items as $item) {
                        if ($this->isSensitiveKey($item->key)) {
                            continue;
                        }

                        $val = $item->value;
                        if (in_array($item->key, $imageKeys) && is_string($val) && ! str_starts_with($val, 'http')) {
                            $val = asset(ltrim($val, '/'));
                        } elseif (is_string($val) && (str_starts_with($val, 'storage/') || str_starts_with($val, '/storage/'))) {
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
            if ($this->isSensitiveKey((string) $key)) {
                return $this->forbidden('Secret configuration is managed through the protected application settings API.');
            }

            $setting = TaggedCache::tags(['settings'])->remember(
                "setting_$key",
                $this->cacheTime,
                fn () => SiteSetting::where('key', $key)->first()
            );

            if (! $setting) {
                return $this->notFound('Site setting not found');
            }

            // We must not mutate the cached object, so we clone or just modify a copy of the value.
            // Wait, actually Laravel Cache sometimes caches the object reference.
            // We can return a cloned array or model.
            $data = $setting->toArray();

            $val = $data['value'];
            $imageKeys = ['navbar_image', 'footer_image', 'register_image', 'tab_icon', 'navbar_logo'];

            if (in_array($key, $imageKeys) && is_string($val) && ! str_starts_with($val, 'http')) {
                $data['value'] = asset(ltrim($val, '/'));
            } elseif (is_string($val) && (str_starts_with($val, 'storage/') || str_starts_with($val, '/storage/'))) {
                $data['value'] = asset(ltrim($val, '/'));
            }

            return $this->success($data, 'Site setting retrieved successfully');
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
        $data = $request->validated();
        $this->rejectSensitiveKey((string) $data['key']);

        try {
            DB::beginTransaction();

            if ($request->hasFile('value')) {
                $file = $request->file('value');
                $path = $imageStorage->store($file, 'settings', 1200, 1200);
                $data['value'] = '/storage/'.$path;
            }

            $setting = SiteSetting::create($data);

            TaggedCache::tags(['settings'])->flush();

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
        $this->rejectSensitiveKey((string) $key);

        try {
            DB::beginTransaction();

            $setting = SiteSetting::firstOrNew(['key' => $key]);

            if ($request->hasFile('value')) {
                $file = $request->file('value');
                $path = $imageStorage->store($file, 'settings', 1200, 1200);
                $setting->value = '/storage/'.$path;
            } else {
                $value = $request->validated()['value'];
                $baseUrl = rtrim(asset(''), '/');
                if (is_string($value) && str_starts_with($value, $baseUrl)) {
                    $value = str_replace($baseUrl, '', $value);
                }
                $setting->value = $value;
            }
            $setting->save();

            TaggedCache::tags(['settings'])->flush();

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

            if (! $setting) {
                return $this->notFound('Site setting not found');
            }

            $setting->delete();

            TaggedCache::tags(['settings'])->flush();

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
    public function batchUpdate(Request $request, OptimizedImageStorage $imageStorage)
    {
        foreach (array_keys($request->all()) as $key) {
            if (! in_array($key, ['_token', '_method'], true)) {
                $this->rejectSensitiveKey((string) $key);
            }
        }

        try {
            DB::beginTransaction();

            foreach ($request->all() as $key => $value) {
                if (in_array($key, ['_token', '_method'], true) || ! preg_match('/^[A-Za-z0-9_.-]{1,100}$/', (string) $key)) {
                    continue;
                }

                if ($request->hasFile($key)) {
                    $request->validate([
                        $key => ['image', 'extensions:jpg,jpeg,png,webp,ico', 'mimes:jpg,jpeg,png,webp,ico', 'dimensions:max_width=4096,max_height=4096', 'max:2048'],
                    ]);
                    $file = $request->file($key);
                    $path = $imageStorage->store($file, 'settings', 1200, 1200);
                    $value = '/storage/'.$path;
                }

                if ($value !== null && ! is_array($value)) {
                    $baseUrl = rtrim(asset(''), '/');
                    if (is_string($value) && str_starts_with($value, $baseUrl)) {
                        $value = str_replace($baseUrl, '', $value);
                    }
                    SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
                }
            }

            TaggedCache::tags(['settings'])->flush();

            DB::commit();

            return $this->success([], 'Settings updated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }

    private function isSensitiveKey(string $key): bool
    {
        return (bool) preg_match('/(?:password|secret|token|api[_-]?key|private[_-]?key|hmac|credential)/i', $key);
    }

    private function rejectSensitiveKey(string $key): void
    {
        abort_if(
            $this->isSensitiveKey($key),
            422,
            'Secret configuration must use the protected application settings API.'
        );
    }
}
