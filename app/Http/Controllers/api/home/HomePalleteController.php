<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ThemeRequest;
use App\Models\WebsiteThemes;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HomePalleteController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $themes = TaggedCache::tags(['themes'])->remember('home_themes_all', 3600, function () {
                return WebsiteThemes::get();
            });

            return $this->success($themes, 'Themes loaded');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    public function show(WebsiteThemes $pallete)
    {
        try {
            return $this->success($pallete, 'theme fetched successfully');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    public function create(ThemeRequest $request)
    {
        try {
            $data = $request->validated();

            $data['slug'] = Str::slug($data['name']).'-'.'pallete';

            $theme = WebsiteThemes::create($data);
            $this->clearCache();

            return $this->success($theme, 'Theme created successfully');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('Something went wrong', 500);
        }
    }

    public function update(ThemeRequest $request, WebsiteThemes $pallete)
    {
        try {
            $data = $request->validated();
            $data['slug'] = Str::slug($data['name']).'-'.'palleteUpdated';
            $pallete->update($data);
            $this->clearCache();

            return $this->success($pallete, 'Pallete updated Successfully');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    public function delete(WebsiteThemes $pallete)
    {
        try {
            $pallete->delete();
            $this->clearCache();

            return $this->success($pallete, 'theme trashed');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('something went wrong', 500);
        }
    }

    public function clearCache()
    {
        TaggedCache::tags(['themes'])->flush();
    }
}
