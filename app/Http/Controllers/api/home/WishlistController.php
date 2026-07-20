<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Home\ProductResource;
use App\Models\Products;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $items = Wishlist::with(['product' => function ($query) {
            $query->with(['category', 'brand', 'stocks'])
                  ->withAvg('reviews', 'rating')
                  ->withCount('reviews');
        }])
            ->whereHas('product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        $items->getCollection()->transform(fn ($item) => $item->product);

        return ProductResource::collection($items);
    }

    public function toggle(Request $request, int $productId)
    {
        $product = $this->resolveProduct($productId);

        $wishlist = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();

            return $this->success(['wishlisted' => false], 'Removed from wishlist.');
        }

        Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return $this->success(['wishlisted' => true], 'Added to wishlist.');
    }

    public function destroy(Request $request, int $productId)
    {
        $product = $this->resolveProduct($productId);

        Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        return $this->success([], 'Removed from wishlist.');
    }

    private function resolveProduct(int $productId): Products
    {
        $product = Products::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            abort(response()->json([
                'success' => false,
                'message' => 'Product not found',
                'data' => null,
                'errors' => [],
            ], 404));
        }

        return $product;
    }
}
