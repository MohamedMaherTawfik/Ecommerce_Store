<?php

namespace App\Exports;

use App\Models\Products;
use App\Support\SpreadsheetCellSanitizer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Products::query()->with(['category', 'brand', 'stocks']);
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
            'Status',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            SpreadsheetCellSanitizer::forExport($product->name),
            SpreadsheetCellSanitizer::forExport($product->sku),
            $product->price,
            $product->stocks ? $product->stocks->quantity : 0,
            SpreadsheetCellSanitizer::forExport($product->category?->name ?? ''),
            SpreadsheetCellSanitizer::forExport($product->brand?->name ?? ''),
            $product->is_active ? 'Active' : 'Inactive',
        ];
    }
}
