<?php

namespace App\Exports;

use App\Models\Orders;
use App\Support\SpreadsheetCellSanitizer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Orders::query()->with('user');
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Customer Name',
            'Email',
            'Total Amount',
            'Status',
            'Payment Status',
            'Created At',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            SpreadsheetCellSanitizer::forExport($order->user?->name ?? 'Guest'),
            SpreadsheetCellSanitizer::forExport($order->user?->email ?? ''),
            $order->total,
            $order->status,
            $order->payment_status,
            $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
