<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $items = Banner::orderBy('sort_order')->get();

            return $this->success($items, 'Banners retrieved successfully');
        } catch (\Exception $e) {
            Log::info($e->getMessage());

            return $this->error('Internal Server Error');
        }
    }

    public function show($id)
    {
        try {
            $item = Banner::find($id);
            if (! $item) {
                return $this->notFound('Banner not found');
            }

            return $this->success($item, 'Banner retrieved successfully');
        } catch (\Exception $e) {
            Log::info($e->getMessage());

            return $this->error('Internal Server Error');
        }
    }

    public function store(BannerRequest $request)
    {
        try {
            $item = Banner::create($request->validated());
            TaggedCache::tags(['banners'])->flush();

            return $this->success($item, 'Banner created successfully');
        } catch (\Exception $e) {
            Log::info($e->getMessage());

            return $this->error('Internal Server Error');
        }
    }

    public function update(BannerRequest $request, $id)
    {
        try {
            $item = Banner::find($id);
            if (! $item) {
                return $this->notFound('Banner not found');
            }
            $item->update($request->validated());
            TaggedCache::tags(['banners'])->flush();

            return $this->success($item, 'Banner updated successfully');
        } catch (\Exception $e) {
            Log::info($e->getMessage());

            return $this->error('Internal Server Error');
        }
    }

    public function destroy($id)
    {
        try {
            $item = Banner::find($id);
            if (! $item) {
                return $this->notFound('Banner not found');
            }
            $item->delete();
            TaggedCache::tags(['banners'])->flush();

            return $this->success(null, 'Banner deleted successfully');
        } catch (\Exception $e) {
            Log::info($e->getMessage());

            return $this->error('Internal Server Error');
        }
    }
}
