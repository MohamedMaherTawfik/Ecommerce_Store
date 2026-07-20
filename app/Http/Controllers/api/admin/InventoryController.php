<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryStockRequest;
use App\Models\ProductVariant;
use App\Models\Products;

class InventoryController extends Controller
{
    use ApiResponse;

    public function lowStock()
    {
        return $this->success(Products::whereNotNull('low_stock_threshold')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->paginate(20), 'Low stock products loaded.');
    }

    public function outOfStock()
    {
        return $this->success(Products::where('stock_status', 'out_of_stock')->orWhere('stock_quantity', '<=', 0)->paginate(20), 'Out of stock products loaded.');
    }

    public function updateProduct(InventoryStockRequest $request, int $product)
    {
        $model = Products::findOrFail($product);
        $model->update($request->validated());

        return $this->success($model->fresh(), 'Product stock updated.');
    }

    public function updateVariant(InventoryStockRequest $request, int $variant)
    {
        $model = ProductVariant::findOrFail($variant);
        $model->update($request->safe()->only(['stock_quantity', 'low_stock_threshold']));

        return $this->success($model->fresh(), 'Variant stock updated.');
    }
}
