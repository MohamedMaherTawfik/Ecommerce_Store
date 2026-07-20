<?php

namespace App\Exports;

use App\Models\Orders;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Orders::with('user')->get();
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
            $order->user ? $order->user->name : 'Guest',
            $order->user ? $order->user->email : '',
            $order->total,
            $order->status,
            $order->payment_status,
            $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
