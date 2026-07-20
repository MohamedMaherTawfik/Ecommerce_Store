<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; margin: 32px;">
    <h1 style="margin-bottom: 4px;">Invoice {{ $invoice->invoice_number }}</h1>
    <p style="margin-top: 0;">Issued at: {{ optional($invoice->issued_at)->format('Y-m-d H:i') }}</p>

    <table style="width: 100%; margin: 24px 0;">
        <tr>
            <td style="vertical-align: top;">
                <strong>{{ $invoice->company_name }}</strong><br>
                {{ $invoice->company_email }}<br>
                {{ $invoice->company_phone }}<br>
                {{ $invoice->company_address }}<br>
                Tax No: {{ $invoice->company_tax_number ?: '-' }}
            </td>
            <td style="vertical-align: top; text-align: right;">
                <strong>{{ $invoice->customer_name }}</strong><br>
                {{ $invoice->customer_email }}<br>
                {{ $invoice->customer_phone }}
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse;" border="1" cellpadding="8">
        <thead>
            <tr>
                <th align="left">Product</th>
                <th align="right">Qty</th>
                <th align="right">Unit</th>
                <th align="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->order->items as $item)
                <tr>
                    <td>{{ $item->product_name ?: $item->product?->name }}</td>
                    <td align="right">{{ $item->quantity }}</td>
                    <td align="right">{{ number_format((float) ($item->unit_price ?: $item->price), 2) }}</td>
                    <td align="right">{{ number_format((float) ($item->total_price ?: $item->price), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 24px; text-align: right;">
        <p>Subtotal: {{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</p>
        <p>Discount: {{ $invoice->currency }} {{ number_format((float) $invoice->discount_amount, 2) }}</p>
        <p>Tax: {{ $invoice->currency }} {{ number_format((float) $invoice->tax_amount, 2) }}</p>
        <p>Shipping: {{ $invoice->currency }} {{ number_format((float) $invoice->shipping_amount, 2) }}</p>
        <h2>Total: {{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</h2>
    </div>
</body>
</html>
