<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Requests\Admin\DealRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DealController extends Controller
{
    use ApiResponse;

    private $cacheTime = 600;

    // =========================
    // INDEX
    // =========================
    public function index()
    {
        try {
            $deals = \App\Support\TaggedCache::tags(['deals'])->remember(
                'deals_all',
                $this->cacheTime,
                fn() => Deal::orderBy('sort_order')->get()
            );

            return $this->success($deals, 'Deals retrieved successfully');
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
            $deal = \App\Support\TaggedCache::tags(['deals'])->remember(
                "deal_$id",
                $this->cacheTime,
                fn() => Deal::find($id)
            );

            if (!$deal) {
                return $this->notFound('Deal not found');
            }

            return $this->success($deal, 'Deal retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // STORE
    // =========================
    public function store(DealRequest $request)
    {
        try {
            DB::beginTransaction();

            $deal = Deal::create($request->validated());

            \App\Support\TaggedCache::tags(['deals'])->flush();

            DB::commit();

            return $this->success($deal, 'Deal created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(DealRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $deal = Deal::find($id);

            if (!$deal) {
                return $this->notFound('Deal not found');
            }

            $deal->update($request->validated());

            \App\Support\TaggedCache::tags(['deals'])->flush();

            DB::commit();

            return $this->success($deal, 'Deal updated successfully');
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

            $deal = Deal::find($id);

            if (!$deal) {
                return $this->notFound('Deal not found');
            }

            $deal->delete();

            \App\Support\TaggedCache::tags(['deals'])->flush();

            DB::commit();

            return $this->success([], 'Deal deleted successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }
}


