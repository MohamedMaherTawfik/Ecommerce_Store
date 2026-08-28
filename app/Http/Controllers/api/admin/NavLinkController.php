<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NavLinkRequest;
use App\Models\NavLink;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NavLinkController extends Controller
{
    use ApiResponse;

    private $cacheTime = 3600;

    // =========================
    // INDEX (CRITICAL CACHE)
    // =========================
    public function index()
    {
        try {
            $links = TaggedCache::tags(['navlinks'])->remember(
                'navlinks_all',
                $this->cacheTime,
                fn () => NavLink::orderBy('sort_order')->get()
            );

            return $this->success($links, 'Nav links retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // SHOW
    // =========================
    public function show($id)
    {
        try {
            $link = TaggedCache::tags(['navlinks'])->remember(
                "navlink_$id",
                $this->cacheTime,
                fn () => NavLink::find($id)
            );

            if (! $link) {
                return $this->notFound('Nav link not found');
            }

            return $this->success($link, 'Nav link retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // STORE
    // =========================
    public function store(NavLinkRequest $request)
    {
        try {
            DB::beginTransaction();

            $link = NavLink::create($request->validated());

            TaggedCache::tags(['navlinks'])->flush();

            DB::commit();

            return $this->success($link, 'Nav link created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(NavLinkRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $link = NavLink::find($id);

            if (! $link) {
                return $this->notFound('Nav link not found');
            }

            $link->update($request->validated());

            TaggedCache::tags(['navlinks'])->flush();

            DB::commit();

            return $this->success($link, 'Nav link updated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // DELETE
    // =========================
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $link = NavLink::find($id);

            if (! $link) {
                return $this->notFound('Nav link not found');
            }

            $link->delete();

            TaggedCache::tags(['navlinks'])->flush();

            DB::commit();

            return $this->success([], 'Nav link deleted successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }
}
