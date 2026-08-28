<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeatureRequest;
use App\Models\Feature;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeatureController extends Controller
{
    use ApiResponse;

    private $cacheTime = 600;

    // =========================
    // INDEX
    // =========================
    public function index()
    {
        try {
            $features = TaggedCache::tags(['features'])->remember(
                'features_all',
                $this->cacheTime,
                fn () => Feature::orderBy('sort_order')->get()
            );

            return $this->success($features, 'Features retrieved successfully');
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
            $feature = TaggedCache::tags(['features'])->remember(
                "feature_$id",
                $this->cacheTime,
                fn () => Feature::find($id)
            );

            if (! $feature) {
                return $this->notFound('Feature not found');
            }

            return $this->success($feature, 'Feature retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // STORE
    // =========================
    public function store(FeatureRequest $request)
    {
        try {
            DB::beginTransaction();

            $feature = Feature::create($request->validated());

            TaggedCache::tags(['features'])->flush();

            DB::commit();

            return $this->success($feature, 'Feature created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(FeatureRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $feature = Feature::find($id);

            if (! $feature) {
                return $this->notFound('Feature not found');
            }

            $feature->update($request->validated());

            TaggedCache::tags(['features'])->flush();

            DB::commit();

            return $this->success($feature, 'Feature updated successfully');
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

            $feature = Feature::find($id);

            if (! $feature) {
                return $this->notFound('Feature not found');
            }

            $feature->delete();

            TaggedCache::tags(['features'])->flush();

            DB::commit();

            return $this->success([], 'Feature deleted successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Internal Server Error');
        }
    }
}
