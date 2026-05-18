<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice['invoice_no'] }}</title>
    <style>
        @page { size: A4; margin: 0; }

        @media print {
            body { margin: 0; background: #fff; }
            .no-print { display: none !important; }
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #F3F4F6;
            color: #111827;
            font-size: 12px;
            line-height: 1.5;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 12mm 14mm 10mm;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 45%; left: 50%;
            transform: translate(-50%, -50%) rotate(-28deg);
            font-size: 88px; font-weight: 900;
            color: rgba(220, 38, 38, 0.09);
            pointer-events: none; z-index: 0;
            white-space: nowrap; letter-spacing: 4px;
        }

        .content { position: relative; z-index: 1; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; gap: 20px; }
        .header-left .logo { max-width: 56px; max-height: 48px; object-fit: contain; display: block; margin-bottom: 8px; }
        .company-name { font-size: 18px; font-weight: 800; color: #4338CA; }
        .company-meta { font-size: 10.5px; color: #374151; line-height: 1.65; margin-top: 5px; }
        .header-right { text-align: right; flex-shrink: 0; }
        .invoice-title { font-size: 24px; font-weight: 900; color: #111827; letter-spacing: 1px; }
        .invoice-refs { font-size: 10.5px; color: #6B7280; margin-top: 9px; line-height: 1.75; }
        .invoice-refs strong { color: #111827; }

        .divider { height: 3px; background: linear-gradient(90deg, #4338CA 0%, #818CF8 60%, #E0E7FF 100%); border-radius: 2px; margin: 12px 0; }
        .divider-light { height: 1px; background: #E5E7EB; margin: 10px 0; }

        .meta-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 14px; }
        .addr-label { font-size: 9.5px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.9px; margin-bottom: 4px; }
        .addr-name { font-size: 13px; font-weight: 700; color: #111827; }
        .addr-detail { font-size: 10.5px; color: #374151; line-height: 1.65; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11px; }
        thead tr { background: #4338CA; }
        thead th { padding: 7px 6px; color: #fff; font-size: 10.5px; font-weight: 600; text-align: left; text-transform: uppercase; letter-spacing: 0.4px; }
        thead th.r { text-align: right; }
        thead th.c { text-align: center; }
        tbody tr:nth-child(even) { background: #F9FAFB; }
        tbody tr:nth-child(odd) { background: #fff; }
        tbody td { padding: 7px 6px; color: #111827; border-bottom: 1px solid #F3F4F6; vertical-align: top; }
        tfoot tr { background: #EEF2FF; border-top: 2px solid #4338CA; }
        tfoot td { padding: 8px 6px; font-weight: 700; font-size: 12px; }
        .r { text-align: right; }
        .c { text-align: center; }

        .summary { width: 200px; margin-left: auto; }
        .summary-row { display: flex; justify-content: space-between; font-size: 11px; padding: 4px 0; border-bottom: 1px solid #F3F4F6; color: #374151; }
        .summary-row:last-child { border-bottom: none; }
        .summary-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: 800; color: #111827; padding: 8px 0 4px; border-top: 2px solid #4338CA; margin-top: 4px; }
        .due-row { color: #DC2626; font-weight: 600; }

        .footer-msg { text-align: center; font-size: 11px; color: #6B7280; margin-top: 16px; padding: 10px; border: 1px solid #E5E7EB; border-radius: 6px; }
        .sys-footer { display: flex; justify-content: space-between; font-size: 9.5px; color: #9CA3AF; margin-top: 14px; padding-top: 8px; border-top: 1px solid #F3F4F6; }
    </style>
</head>
<body>
@php
    $showDiscount = $settings['show_discount'];
    $showGst      = $settings['show_gst'];
    $showBatch    = $settings['show_batch'];
    $showExpiry   = $settings['show_expiry'];
    $hasDiscount  = $showDiscount && $invoice['total_discount_amount'] > 0;
    $totalCols    = 4 + ($hasDiscount?1:0) + ($showGst?1:0) + ($showBatch?1:0) + ($showExpiry?1:0);
@endphp

<div class="page">
    @if($is_cancelled)
        <div class="watermark">CANCELLED</div>
    @endif

    <div class="content">

        <div class="header">
            <div class="header-left">
                @if($settings['show_logo'] && !empty($shop['logo']))
                    <img class="logo" src="{{ $shop['logo'] }}" alt="{{ $shop['name'] }}">
                @endif
                <div class="company-name">{{ $shop['name'] }}</div>
                <div class="company-meta">
                    @if(!empty($shop['business_name']) && $shop['business_name'] !== $shop['name'])
                        {{ $shop['business_name'] }}<br>
                    @endif
                    @if(!empty($shop['address'])){{ $shop['address'] }}<br>@endif
                    @if(!empty($shop['mobile_no']))Mobile: {{ $shop['mobile_no'] }}<br>@endif
                    @if(!empty($shop['email'])){{ $shop['email'] }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">RETAIL INVOICE</div>
                <div class="invoice-refs">
                    Invoice: <strong>{{ $invoice['invoice_no'] }}</strong><br>
                    Date: <strong>{{ $invoice['invoice_date_short'] ?? $invoice['invoice_date_display'] }}</strong><br>
                    Status: <strong>{{ $invoice['payment_status'] }}</strong>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="meta-row">
            <div>
                <div class="addr-label">Customer</div>
                <div class="addr-name">{{ $customer['name'] }}</div>
                <div class="addr-detail">
                    @if(!empty($customer['mobile_no']))Mobile: {{ $customer['mobile_no'] }}<br>@endif
                    @if(!empty($customer['address'])){{ $customer['address'] }}@endif
                </div>
            </div>
            <div>
                <div class="addr-label">Payment</div>
                <div class="addr-detail" style="margin-top:4px;">
                    {{ $summary['payment_modes_summary'] }}<br>
                    <span style="color:#374151;">{{ $invoice['sale_status'] }}</span>
                </div>
            </div>
        </div>

        <div class="divider-light"></div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    @if($showBatch)<th>Batch</th>@endif
                    @if($showExpiry)<th>Expiry</th>@endif
                    <th class="c">Qty</th>
                    <th class="r">Rate</th>
                    @if($hasDiscount)<th class="r">Discount</th>@endif
                    @if($showGst)<th class="r">GST</th>@endif
                    <th class="r">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['sr_no'] }}</td>
                    <td>
                        <span style="font-weight:600;">{{ $item['display_name'] }}</span>
                        @if(!empty($item['attribute_summary']))
                            <div style="font-size:10px;color:#6B7280;">{{ $item['attribute_summary'] }}</div>
                        @endif
                    </td>
                    @if($showBatch)<td>{{ $item['batch_no'] ?? '—' }}</td>@endif
                    @if($showExpiry)<td>{{ $item['expiry_display'] ?? '—' }}</td>@endif
                    <td class="c">{{ $item['qty'] }}</td>
                    <td class="r">₹{{ number_format($item['unit_price'], 2) }}</td>
                    @if($hasDiscount)<td class="r">₹{{ number_format($item['discount_amount'], 2) }}</td>@endif
                    @if($showGst)<td class="r">₹{{ number_format($item['gst_amount'], 2) }}</td>@endif
                    <td class="r">₹{{ number_format($item['total_amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $totalCols - 1 }}" class="r" style="font-size:11px;font-weight:700;color:#4338CA;">TOTAL</td>
                    <td class="r">₹{{ number_format($invoice['grand_total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="summary">
            <div class="summary-row"><span>Subtotal</span><span>₹{{ number_format($invoice['subtotal'], 2) }}</span></div>
            @if($hasDiscount)
                <div class="summary-row"><span>Discount</span><span>₹{{ number_format($invoice['total_discount_amount'], 2) }}</span></div>
            @endif
            @if($showGst && $invoice['gst_amount'] > 0)
                <div class="summary-row"><span>GST</span><span>₹{{ number_format($invoice['gst_amount'], 2) }}</span></div>
            @endif
            <div class="summary-total">
                <span>Grand Total</span>
                <span>₹{{ number_format($invoice['grand_total'], 2) }}</span>
            </div>
            @if($invoice['paid_amount'] < $invoice['grand_total'])
                <div class="summary-row" style="margin-top:6px;"><span>Paid</span><span>₹{{ number_format($invoice['paid_amount'], 2) }}</span></div>
                <div class="summary-row due-row"><span>Due</span><span>₹{{ number_format($invoice['due_amount'], 2) }}</span></div>
            @endif
        </div>

        @if(!empty($settings['footer_message']))
            <div class="footer-msg">{{ $settings['footer_message'] }}</div>
        @endif

        <div class="sys-footer">
            <span>This is a system generated invoice</span>
            <span>Page 1 / 1</span>
        </div>

    </div>
</div>
</body>
</html>
