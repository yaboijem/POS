@php
    $paymentLabel = payment_method_label($sale->payment_method, $sale->payment_label);
    $discountLabel = $sale->discount_type === 'percent'
        ? 'Discount ('.rtrim(rtrim(number_format((float) $sale->discount_value, 2, '.', ''), '0'), '.').'%)'
        : 'Discount';
@endphp

<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'DejaVu Sans', Arial, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.45; width: 100%; max-width: 80mm; margin: 0 auto; background: #ffffff;">

    <div style="text-align: center;">
        <div style="font-size: 16px; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; line-height: 1.25;">
            {{ setting('business_name', 'Karinderya') }}
        </div>
        @if (setting('business_address'))
            <div style="margin-top: 3px; font-size: 10px; color: #475569;">{{ setting('business_address') }}</div>
        @endif
        <div style="margin-top: 2px; font-size: 10px; color: #475569;">
            @if (setting('business_phone')) {{ setting('business_phone') }} @endif
            @if (setting('business_phone') && setting('business_email')) · @endif
            @if (setting('business_email')) {{ setting('business_email') }} @endif
        </div>
    </div>

    <div style="border-top: 1px dashed #94a3b8; margin: 9px 0;"></div>

    <div style="text-align: center;">
        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.08em;">SALES RECEIPT</div>
        <div style="margin-top: 2px; font-size: 11px; font-weight: 700;">{{ $sale->transaction_number }}</div>
        <div style="margin-top: 1px; font-size: 10px; color: #475569;">{{ $sale->sold_at->format('M j, Y · g:i A') }}</div>
        <div style="font-size: 10px; color: #475569;">Cashier: {{ $sale->cashier?->name ?? 'Staff' }}</div>
    </div>

    <div style="border-top: 1px dashed #94a3b8; margin: 9px 0;"></div>

    <table style="width: 100%; border-collapse: collapse;">
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td colspan="2" style="padding: 3px 0 0; font-size: 11px; font-weight: 700; vertical-align: top;">{{ $item->product_name }}</td>
                </tr>
                <tr>
                    <td style="padding: 0 0 4px; font-size: 10px; color: #475569; vertical-align: top;">
                        {{ quantity($item->quantity) }} {{ $item->unit }} × {{ peso($item->unit_price) }}
                    </td>
                    <td style="padding: 0 0 4px; text-align: right; font-size: 11px; font-weight: 600; vertical-align: top; white-space: nowrap;">
                        {{ peso($item->line_total) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="border-top: 1px dashed #94a3b8; margin: 6px 0 7px;"></div>

    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
        <tr>
            <td style="padding: 1px 0; color: #475569;">Subtotal</td>
            <td style="padding: 1px 0; text-align: right; white-space: nowrap;">{{ peso($sale->subtotal) }}</td>
        </tr>
        @if ((float) $sale->discount_amount > 0)
            <tr>
                <td style="padding: 1px 0; color: #475569;">{{ $discountLabel }}</td>
                <td style="padding: 1px 0; text-align: right; white-space: nowrap;">−{{ peso($sale->discount_amount) }}</td>
            </tr>
        @endif
        @if ((float) $sale->tax_amount > 0)
            <tr>
                <td style="padding: 1px 0; color: #475569;">Tax</td>
                <td style="padding: 1px 0; text-align: right; white-space: nowrap;">{{ peso($sale->tax_amount) }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding: 6px 0 2px; border-top: 1px solid #cbd5e1; font-size: 12px; font-weight: 800;">TOTAL</td>
            <td style="padding: 6px 0 2px; border-top: 1px solid #cbd5e1; text-align: right; font-size: 14px; font-weight: 800; white-space: nowrap;">{{ peso($sale->total) }}</td>
        </tr>
    </table>

    <div style="border-top: 1px dashed #94a3b8; margin: 7px 0;"></div>

    <table style="width: 100%; border-collapse: collapse; font-size: 10px; color: #475569;">
        <tr>
            <td style="padding: 1px 0;">Payment</td>
            <td style="padding: 1px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ $paymentLabel }} · recorded</td>
        </tr>
        @if ($sale->payment_reference)
            <tr>
                <td style="padding: 1px 0;">Reference</td>
                <td style="padding: 1px 0; text-align: right; color: #0f172a;">{{ $sale->payment_reference }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding: 1px 0;">Amount received</td>
            <td style="padding: 1px 0; text-align: right; color: #0f172a;">{{ peso($sale->amount_tendered) }}</td>
        </tr>
        @if ((float) $sale->change_amount > 0)
            <tr>
                <td style="padding: 1px 0;">Change</td>
                <td style="padding: 1px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ peso($sale->change_amount) }}</td>
            </tr>
        @endif
    </table>

    @if ($sale->notes)
        <div style="margin-top: 7px; font-size: 10px; color: #475569;">Note: {{ $sale->notes }}</div>
    @endif

    <div style="border-top: 1px dashed #94a3b8; margin: 9px 0;"></div>

    <div style="text-align: center;">
        <div style="font-size: 11px; font-weight: 600;">{{ setting('receipt_footer', 'Salamat po! Please come again.') }}</div>
        <div style="margin-top: 5px; font-size: 9px; color: #64748b;">This is an internal sales receipt, not a BIR official invoice.</div>
        <div style="margin-top: 2px; font-size: 9px; color: #64748b;">Thank you for supporting our small business.</div>
    </div>
</div>
