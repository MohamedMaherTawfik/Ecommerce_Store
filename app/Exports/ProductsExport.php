<?php

namespace App\Exports;

use App\Models\Products;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Products::with(['category', 'brand', 'stocks'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'SKU',
            'Price',
            'Quantity',
            'Category',
            'Brand',
            'Status'
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku,
            $product->price,
            $product->stocks ? $product->stocks->quantity : 0,
            $product->category ? $product->category->name : '',
            $product->brand ? $product->brand->name : '',
            $product->is_active ? 'Active' : 'Inactive',
        ];
    }
}
