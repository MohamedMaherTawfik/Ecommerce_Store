<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use App\Services\Checkout\InvoiceService;

class InvoiceController extends Controller
{
    public function download(InvoiceService $invoices, int $order)
    {
        $query = Orders::with(['invoice', 'user', 'items.product']);
        $user = auth()->user();

        if (! $user?->hasPermission('invoices.manage')) {
            $query->where('user_id', $user?->id);
        }

        $orderModel = $query->findOrFail($order);
        $invoice = $orderModel->invoice ?: $invoices->createForOrder($orderModel);

        return response($invoices->renderHtml($invoice), 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "attachment; filename=\"{$invoice->invoice_number}.html\"",
        ]);
    }
}
