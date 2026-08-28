<?php

namespace App\Services\Checkout;

use App\Models\Products;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function ensureAvailable(Collection $items): void
    {
        foreach ($items as $item) {
            $quantity = (int) $item->quantity;

            if ($item->product_variant_id) {
                $variant = ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->first();
                if (! $variant || (! $variant->product?->allow_backorder && $variant->stock_quantity < $quantity)) {
                    throw ValidationException::withMessages(['stock' => ['Selected variant is out of stock.']]);
                }

                continue;
            }

            $product = Products::whereKey($item->product_id)->lockForUpdate()->first();
            $stock = Stock::where('product_id', $item->product_id)->lockForUpdate()->first();
            $available = $stock ? (int) $stock->quantity : (int) ($product?->stock_quantity ?? 0);

            if (! $product || (! $product->allow_backorder && $available < $quantity)) {
                throw ValidationException::withMessages(['stock' => ["{$item->product?->name} is out of stock."]]);
            }
        }
    }

    public function deduct(Collection $items): void
    {
        foreach ($items as $item) {
            $quantity = (int) $item->quantity;

            if ($item->product_variant_id) {
                ProductVariant::whereKey($item->product_variant_id)->decrement('stock_quantity', $quantity);

                continue;
            }

            $stock = Stock::where('product_id', $item->product_id)->first();
            if ($stock) {
                $stock->decrement('quantity', $quantity);
            }

            Products::whereKey($item->product_id)->decrement('stock_quantity', $quantity);
        }
    }

    public function restore(Collection $items): void
    {
        foreach ($items as $item) {
            $quantity = (int) $item->quantity;

            if ($item->product_variant_id) {
                ProductVariant::whereKey($item->product_variant_id)->increment('stock_quantity', $quantity);

                continue;
            }

            $stock = Stock::where('product_id', $item->product_id)->first();
            if ($stock) {
                $stock->increment('quantity', $quantity);
            }

            Products::whereKey($item->product_id)->increment('stock_quantity', $quantity);
        }
    }
}
