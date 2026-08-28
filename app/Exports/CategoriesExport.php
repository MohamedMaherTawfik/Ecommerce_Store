<?php

namespace App\Exports;

use App\Models\Categories;
use App\Support\SpreadsheetCellSanitizer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoriesExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Categories::query();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Status',
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            SpreadsheetCellSanitizer::forExport($category->name),
            SpreadsheetCellSanitizer::forExport($category->slug),
            $category->is_active ? 'Active' : 'Inactive',
        ];
    }
}
