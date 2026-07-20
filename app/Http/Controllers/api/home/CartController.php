<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Home\ApplyCouponRequest;
use App\Http\Requests\Home\CartItemQuantityRequest;
use App\Http\Resources\Home\CartResource;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Products;
use App\Services\Home\CartPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CartPricingService $pricing)
    {
    }

    public function addToCart(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
            'size' => 'nullable|string',
            'color' => 'nullable|string',
        ]);
        $product = Products::with('stocks')->findOrFail($id);
        $cart = auth()->user()->cart()->firstOrCreate([]);
        $quantity = $request->input('quantity', 1);
        $stock = (int) ($product->stocks?->quantity ?? 0);

        if ($stock < $quantity) {
            return $this->error('Product is out of stock or requested quantity is unavailable.', 422);
        }

        $size = $request->input('size', 'L');
        $color = $request->input('color', 'black');
        $item = CartItems::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('size', $size)
            ->where('color', $color)
            ->first();

        if ($item) {
            if ($stock < ($item->quantity + $quantity)) {
                return $this->error('Requested quantity exceeds available stock.', 422);
            }

            $item->increment('quantity', $quantity);
            $item->update(['price' => \Illuminate\Support\Facades\DB::raw("quantity * {$product->price}")]);
        } else {
            CartItems::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'size' => $size,
                'color' => $color,
                'price' => $product->price * $quantity,
            ]);
        }
        return $this->success(new CartResource($cart->fresh(['items.product.stocks', 'items.product.category', 'items.product.brand', 'coupon'])), 'Added To Cart Successfully');
    }

    public function cart()
    {
        $cart = auth()->user()->cart()->with(['items.product.stocks', 'items.product.category', 'items.product.brand', 'coupon'])->firstOrCreate([]);
        return $this->success(new CartResource($cart), 'Cart');
    }

    public function deleteFromCart($id)
    {
        $item = CartItems::whereHas('cart', fn ($query) => $query->where('user_id', auth()->id()))
            ->findOrFail($id);
        $item->delete();
        return $this->success($item, 'Deleted From Cart Successfully');
    }

    public function updateQuantity(CartItemQuantityRequest $request, int $id)
    {
        $item = CartItems::with('product.stocks')
            ->whereHas('cart', fn ($query) => $query->where('user_id', $request->user()->id))
            ->findOrFail($id);

        $quantity = (int) $request->validated('quantity');
        $stock = (int) ($item->product?->stocks?->quantity ?? 0);

        if ($stock < $quantity) {
            return $this->error('Requested quantity exceeds available stock.', 422);
        }

        $item->update([
            'quantity' => $quantity,
            'price' => round((float) $item->product->price * $quantity, 2),
        ]);

        $cart = $request->user()->cart()->with(['items.product.stocks', 'items.product.category', 'items.product.brand', 'coupon'])->first();

        return $this->success(new CartResource($cart), 'Cart updated successfully.');
    }

    public function clearCart()
    {
        $cart = auth()->user()->cart()->first();
        $cart?->items()->delete();
        return $this->success($cart, 'Cart Cleared Successfully');
    }

    public function applyCoupon(ApplyCouponRequest $request)
    {
        $cart = $request->user()->cart()->with(['items.product.stocks', 'items.product.category', 'items.product.brand', 'coupon'])->firstOrCreate([]);
        $cart = $this->pricing->applyCoupon($cart, $request->validated('code'));

        return $this->success(new CartResource($cart), 'Coupon applied successfully.');
    }

    public function removeCoupon(Request $request)
    {
        $cart = $request->user()->cart()->with(['items.product.stocks', 'items.product.category', 'items.product.brand', 'coupon'])->firstOrCreate([]);
        $cart = $this->pricing->removeCoupon($cart);

        return $this->success(new CartResource($cart), 'Coupon removed successfully.');
    }
}
