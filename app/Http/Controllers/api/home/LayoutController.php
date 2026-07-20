<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\Controller;
use App\Http\Controllers\concerns\ApiResponse;
use App\Services\Home\LayoutContentService;
use Illuminate\Support\Facades\Log;

class LayoutController extends Controller
{
    use ApiResponse;

    public function index(LayoutContentService $layoutContent)
    {
        try {
            return $this->success($layoutContent->get(), 'Layout loaded')
                ->setPublic()
                ->setMaxAge(300)
                ->setSharedMaxAge(300);
        } catch (\Throwable $e) {
            Log::info($e->getMessage());
            return $this->error('something went wrong');
        }
    }

    public function clearCache()
    {
        \App\Support\TaggedCache::tags(['layout'])->flush();
    }
}
