<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Models\TrustItem;
use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Requests\Admin\TrustItemRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrustItemController extends Controller
{
    use ApiResponse;

    private $cacheTime = 3600;

    // =========================
    // INDEX
    // =========================
    public function index()
    {
        try {
            $items = \App\Support\TaggedCache::tags(['trust_items'])->remember(
                'trust_items_all',
                $this->cacheTime,
                fn() => TrustItem::orderBy('sort_order')->get()
            );

            return $this->success($items, 'Trust items retrieved successfully');
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
            $item = \App\Support\TaggedCache::tags(['trust_items'])->remember(
                "trust_item_$id",
                $this->cacheTime,
                fn() => TrustItem::find($id)
            );

            if (!$item) {
                return $this->notFound('Trust item not found');
            }

            return $this->success($item, 'Trust item retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // STORE
    // =========================
    public function store(TrustItemRequest $request)
    {
        try {
            DB::beginTransaction();

            $item = TrustItem::create($request->validated());

            \App\Support\TaggedCache::tags(['trust_items'])->flush();

            DB::commit();

            return $this->success($item, 'Trust item created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(TrustItemRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $item = TrustItem::find($id);

            if (!$item) {
                return $this->notFound('Trust item not found');
            }

            $item->update($request->validated());

            \App\Support\TaggedCache::tags(['trust_items'])->flush();

            DB::commit();

            return $this->success($item, 'Trust item updated successfully');
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

            $item = TrustItem::find($id);

            if (!$item) {
                return $this->notFound('Trust item not found');
            }

            $item->delete();

            \App\Support\TaggedCache::tags(['trust_items'])->flush();

            DB::commit();

            return $this->success([], 'Trust item deleted successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }
}


