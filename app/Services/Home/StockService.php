<?php

namespace App\Services\Home;

use App\Models\CartItems;
use App\Models\Stock;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function ensureAvailable(iterable $items): void
    {
        foreach ($items as $item) {
            $stock = Stock::where('product_id', $item->product_id)->lockForUpdate()->first();
            $available = (int) ($stock?->quantity ?? 0);

            if ($available < (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => ["{$item->product?->name} has only {$available} item(s) available."],
                ]);
            }
        }
    }

    public function reduce(iterable $items): void
    {
        foreach ($items as $item) {
            $productId = $item instanceof CartItems ? $item->product_id : $item->product_id;
            $quantity = (int) $item->quantity;
            $stock = Stock::where('product_id', $productId)->lockForUpdate()->first();

            if (! $stock || $stock->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'stock' => ['One or more products are no longer available.'],
                ]);
            }

            $stock->decrement('quantity', $quantity);
        }
    }
}
