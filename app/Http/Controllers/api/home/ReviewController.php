<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Home\ReviewRequest;
use App\Models\Products;
use App\Models\Reviews;
use App\Support\TaggedCache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    use ApiResponse;

    public function store(ReviewRequest $request, int $productId)
    {
        try {
            Products::findOrFail($productId);

            $review = Reviews::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'product_id' => $productId,
                ],
                $request->validated()
            );

            $this->clearCache();

            return $this->success($review->load('user:id,name'), 'Review saved successfully.');
        } catch (ModelNotFoundException) {
            return $this->notFound('Product not found');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());

            return $this->error('Something went wrong.', 500);
        }
    }

    public function clearCache()
    {
        TaggedCache::tags(['products'])->flush();
    }
}
