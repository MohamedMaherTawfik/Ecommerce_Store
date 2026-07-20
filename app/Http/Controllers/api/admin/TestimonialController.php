<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Requests\Admin\TestimonialRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestimonialController extends Controller
{
    use ApiResponse;

    private $cacheTime = 3600;

    // =========================
    // INDEX
    // =========================
    public function index()
    {
        try {
            $items = \App\Support\TaggedCache::tags(['testimonials'])->remember(
                'testimonials_all',
                $this->cacheTime,
                fn() => Testimonial::orderBy('sort_order')->get()
            );

            return $this->success($items, 'Testimonials retrieved successfully');
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
            $item = \App\Support\TaggedCache::tags(['testimonials'])->remember(
                "testimonial_$id",
                $this->cacheTime,
                fn() => Testimonial::find($id)
            );

            if (!$item) {
                return $this->notFound('Testimonial not found');
            }

            return $this->success($item, 'Testimonial retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // STORE
    // =========================
    public function store(TestimonialRequest $request)
    {
        try {
            DB::beginTransaction();

            $item = Testimonial::create($request->validated());

            \App\Support\TaggedCache::tags(['testimonials'])->flush();

            DB::commit();

            return $this->success($item, 'Testimonial created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(TestimonialRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $item = Testimonial::find($id);

            if (!$item) {
                return $this->notFound('Testimonial not found');
            }

            $item->update($request->validated());

            \App\Support\TaggedCache::tags(['testimonials'])->flush();

            DB::commit();

            return $this->success($item, 'Testimonial updated successfully');
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

            $item = Testimonial::find($id);

            if (!$item) {
                return $this->notFound('Testimonial not found');
            }

            $item->delete();

            \App\Support\TaggedCache::tags(['testimonials'])->flush();

            DB::commit();

            return $this->success([], 'Testimonial deleted successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Internal Server Error');
        }
    }
}


