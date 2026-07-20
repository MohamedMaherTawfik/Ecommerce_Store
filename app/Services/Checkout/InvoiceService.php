<?php

namespace App\Services\Checkout;

use App\Models\Invoice;
use App\Models\Orders;
use Illuminate\Support\Str;

class InvoiceService
{
    public function createForOrder(Orders $order): Invoice
    {
        return Invoice::firstOrCreate(
            ['order_id' => $order->id],
            [
                'invoice_number' => $this->nextNumber(),
                'company_name' => config('checkout.company.name'),
                'company_email' => config('checkout.company.email'),
                'company_phone' => config('checkout.company.phone'),
                'company_address' => config('checkout.company.address'),
                'company_tax_number' => config('checkout.company.tax_number'),
                'customer_name' => $order->user?->name ?: 'Customer',
                'customer_email' => $order->user?->email,
                'customer_phone' => $order->phone,
                'billing_address' => $order->billing_address_snapshot,
                'shipping_address' => $order->shipping_address_snapshot,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount ?? $order->discount,
                'tax_amount' => $order->tax_amount ?? $order->tax,
                'shipping_amount' => $order->shipping_amount ?? $order->shipping_cost,
                'total' => $order->total,
                'currency' => $order->currency ?? config('checkout.currency'),
                'issued_at' => now(),
            ]
        );
    }

    public function renderHtml(Invoice $invoice): string
    {
        return view('invoices.simple', ['invoice' => $invoice->loadMissing('order.items.product')])->render();
    }

    private function nextNumber(): string
    {
        $prefix = config('checkout.invoice.prefix', 'INV');
        $next = (int) (Invoice::max('id') + config('checkout.invoice.next_number', 1));

        return $prefix . '-' . now()->format('Y') . '-' . Str::padLeft((string) $next, 6, '0');
    }
}
