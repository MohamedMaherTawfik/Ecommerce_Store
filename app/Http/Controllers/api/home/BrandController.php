<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\Controller;
use App\Http\Controllers\concerns\ApiResponse;
use App\Models\brands;
use Illuminate\Support\Facades\Log;

class BrandController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $brands = \App\Support\TaggedCache::tags(['brands'])->remember('home_brands_all', 3600, function () {
                return brands::query()
                    ->select('id', 'name', 'slug', 'image')
                    ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                    ->orderBy('name')
                    ->paginate(20);
            });

            return $this->success($brands, 'Brands loaded');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());
            return $this->error('something went wrong');
        }
    }

    public function clearCache()
    {
        \App\Support\TaggedCache::tags(['brands'])->flush();
    }
}


