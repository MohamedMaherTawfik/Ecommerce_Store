<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\Controller;
use App\Http\Controllers\concerns\ApiResponse;
use App\Services\Home\HomePageService;
use Illuminate\Support\Facades\Log;

class HomeContentController extends Controller
{
    use ApiResponse;

    public function index(HomePageService $homePage)
    {
        try {
            return $this->success($homePage->get(), 'Home content loaded')
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
        \App\Support\TaggedCache::tags(['home_content'])->flush();
    }
}
