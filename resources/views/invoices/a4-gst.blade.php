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

        /* WATERMARK */
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

        /* HEADER */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; gap: 20px; }
        .header-left .logo { max-width: 56px; max-height: 48px; object-fit: contain; display: block; margin-bottom: 8px; }
        .company-name { font-size: 17px; font-weight: 800; color: #4338CA; }
        .company-meta { font-size: 10.5px; color: #374151; line-height: 1.65; margin-top: 5px; }
        .header-right { text-align: right; flex-shrink: 0; }
        .invoice-title { font-size: 26px; font-weight: 900; color: #111827; letter-spacing: 1.5px; }
        .invoice-refs { font-size: 10.5px; color: #6B7280; margin-top: 9px; line-height: 1.75; }
        .invoice-refs strong { color: #111827; }

        /* DIVIDER */
        .divider { height: 3px; background: linear-gradient(90deg, #4338CA 0%, #818CF8 60%, #E0E7FF 100%); border-radius: 2px; margin: 12px 0; }
        .divider-light { height: 1px; background: #E5E7EB; margin: 10px 0; }

        /* BILL TO / SHIP TO */
        .address-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 14px; }
        .addr-label { font-size: 9.5px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.9px; margin-bottom: 4px; }
        .addr-name { font-size: 13px; font-weight: 700; color: #111827; }
        .addr-detail { font-size: 10.5px; color: #374151; line-height: 1.65; margin-top: 2px; }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11px; }
        thead tr { background: #4338CA; }
        thead th { padding: 7px 6px; color: #fff; font-size: 10.5px; font-weight: 600; text-align: left; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap; }
        thead th.r { text-align: right; }
        thead th.c { text-align: center; }
        tbody tr:nth-child(even) { background: #F9FAFB; }
        tbody tr:nth-child(odd) { background: #fff; }
        tbody td { padding: 7px 6px; color: #111827; border-bottom: 1px solid #F3F4F6; vertical-align: top; }
        tfoot tr { background: #EEF2FF; border-top: 2px solid #4338CA; }
        tfoot td { padding: 8px 6px; font-weight: 700; font-size: 12px; }
        .r { text-align: right; }
        .c { text-align: center; }
        .item-sub { font-size: 10px; color: #6B7280; margin-top: 2px; }

        /* BOTTOM GRID */
        .bottom-grid { display: grid; grid-template-columns: 1fr 200px; gap: 24px; margin-top: 10px; }

        /* GST BREAKDOWN */
        .gst-title { font-size: 11px; font-weight: 700; color: #111827; margin-bottom: 6px; }
        .gst-row { display: flex; justify-content: space-between; font-size: 10.5px; padding: 4px 0; border-bottom: 1px solid #F3F4F6; color: #374151; }
        .gst-row:last-child { border-bottom: none; }

        /* AMOUNT WORDS */
        .amount-words { background: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 6px; padding: 7px 10px; font-size: 10.5px; margin-top: 12px; color: #3730A3; }

        /* SUMMARY */
        .summary-row { display: flex; justify-content: space-between; font-size: 11px; padding: 4px 0; border-bottom: 1px solid #F3F4F6; color: #374151; }
        .summary-row:last-child { border-bottom: none; }
        .summary-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: 800; color: #111827; padding: 8px 0 4px; border-top: 2px solid #4338CA; margin-top: 4px; }
        .due-row { color: #DC2626; font-weight: 600; }

        /* FOOTER */
        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 18px; padding-top: 14px; border-top: 1px solid #E5E7EB; }
        .footer-terms { font-size: 10px; color: #6B7280; max-width: 55%; }
        .footer-terms strong { color: #374151; display: block; margin-bottom: 2px; }
        .sig-box { text-align: center; }
        .sig-image { max-width: 100px; max-height: 38px; object-fit: contain; display: block; margin: 0 auto 4px; }
        .sig-line { width: 120px; border-top: 1px solid #9CA3AF; margin: 32px auto 4px; }
        .sig-label { font-size: 10px; color: #6B7280; }

        /* SYSTEM FOOTER */
        .sys-footer { display: flex; justify-content: space-between; font-size: 9.5px; color: #9CA3AF; margin-top: 14px; padding-top: 8px; border-top: 1px solid #F3F4F6; }
    </style>
</head>
<body>
@php
    $showGst      = $settings['show_gst'];
    $showDiscount = $settings['show_discount'];
    $showBatch    = $settings['show_batch'];
    $showExpiry   = $settings['show_expiry'];
    $showHsn      = $settings['show_hsn'];
    $hasDiscount  = $showDiscount && $invoice['total_discount_amount'] > 0;
    $hasGst       = $showGst && $invoice['gst_amount'] > 0;

    // GST summary grouped by rate
    $gstGroups = [];
    foreach ($items as $item) {
        if ($item['cgst_amount'] > 0) {
            $key = 'CGST ' . number_format($item['cgst_percent'], 2) . '%';
            $gstGroups[$key] = ($gstGroups[$key] ?? 0) + $item['cgst_amount'];
            $key2 = 'SGST ' . number_format($item['sgst_percent'], 2) . '%';
            $gstGroups[$key2] = ($gstGroups[$key2] ?? 0) + $item['sgst_amount'];
        }
        if ($item['igst_amount'] > 0) {
            $key = 'IGST ' . number_format($item['igst_percent'], 2) . '%';
            $gstGroups[$key] = ($gstGroups[$key] ?? 0) + $item['igst_amount'];
        }
    }

    // Column count for tfoot colspan
    $totalCols = 5 + ($showHsn?1:0) + ($showBatch?1:0) + ($showExpiry?1:0) + ($hasDiscount?1:0) + ($showGst?3:0);
@endphp

<div class="page">
    @if($is_cancelled)
        <div class="watermark">CANCELLED</div>
    @endif

    <div class="content">

        {{-- HEADER --}}
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
                    @if(!empty($shop['state'])){{ $shop['state'] }}@if(!empty($shop['state_code'])) - {{ $shop['state_code'] }}@endif<br>@endif
                    @if(!empty($shop['mobile_no']))Mobile: {{ $shop['mobile_no'] }}<br>@endif
                    @if(!empty($shop['email'])){{ $shop['email'] }}<br>@endif
                    @if(!empty($shop['gstin']))<strong>GSTIN:</strong> {{ $shop['gstin'] }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">TAX INVOICE</div>
                <div class="invoice-refs">
                    Invoice: <strong>{{ $invoice['invoice_no'] }}</strong><br>
                    Date: <strong>{{ $invoice['invoice_date_short'] ?? $invoice['invoice_date_display'] }}</strong><br>
                    Status: <strong>{{ $invoice['payment_status'] }}</strong>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        {{-- BILL TO / SHIP TO --}}
        <div class="address-row">
            <div>
                <div class="addr-label">Bill To</div>
                <div class="addr-name">{{ $customer['name'] }}</div>
                <div class="addr-detail">
                    @if(!empty($customer['address'])){{ $customer['address'] }}<br>@endif
                    @if(!empty($customer['mobile_no']))Mobile: {{ $customer['mobile_no'] }}<br>@endif
                    @if(!empty($customer['gstin']))<strong>GSTIN:</strong> {{ $customer['gstin'] }}@endif
                </div>
            </div>
            <div>
                <div class="addr-label">Ship To</div>
                <div class="addr-name">{{ $customer['name'] }}</div>
                <div class="addr-detail">
                    @if(!empty($customer['address'])){{ $customer['address'] }}@endif
                </div>
            </div>
        </div>

        <div class="divider-light"></div>

        {{-- ITEMS TABLE --}}
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    @if($showHsn)<th>HSN / SAC</th>@endif
                    @if($showBatch)<th>Batch</th>@endif
                    @if($showExpiry)<th>Expiry</th>@endif
                    <th class="c">Qty</th>
                    <th class="r">Price</th>
                    @if($hasDiscount)<th class="r">Discount</th>@endif
                    @if($showGst)<th class="r">CGST</th><th class="r">SGST</th><th class="r">IGST</th>@endif
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
                            <div class="item-sub">{{ $item['attribute_summary'] }}</div>
                        @endif
                    </td>
                    @if($showHsn)<td>{{ $item['hsn_code'] ?? '—' }}</td>@endif
                    @if($showBatch)<td>{{ $item['batch_no'] ?? '—' }}</td>@endif
                    @if($showExpiry)<td>{{ $item['expiry_display'] ?? '—' }}</td>@endif
                    <td class="c">{{ $item['qty'] }}</td>
                    <td class="r">₹{{ number_format($item['unit_price'], 2) }}</td>
                    @if($hasDiscount)<td class="r">₹{{ number_format($item['discount_amount'], 2) }}</td>@endif
                    @if($showGst)
                        <td class="r">₹{{ number_format($item['cgst_amount'], 2) }}</td>
                        <td class="r">₹{{ number_format($item['sgst_amount'], 2) }}</td>
                        <td class="r">₹{{ number_format($item['igst_amount'], 2) }}</td>
                    @endif
                    <td class="r">₹{{ number_format($item['total_amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $totalCols - 1 }}" class="r" style="font-size:11px;font-weight:700;color:#4338CA;letter-spacing:.5px;">TOTAL</td>
                    <td class="r">₹{{ number_format($invoice['grand_total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- BOTTOM: GST BREAKDOWN + SUMMARY --}}
        <div class="bottom-grid">
            <div>
                @if($hasGst && count($gstGroups) > 0)
                    <div class="gst-title">GST Breakdown</div>
                    @foreach($gstGroups as $label => $amount)
                        <div class="gst-row">
                            <span>{{ $label }}</span>
                            <span>₹{{ number_format($amount, 2) }}</span>
                        </div>
                    @endforeach
                @endif

                @if(!empty($invoice['amount_in_words']))
                    <div class="amount-words" style="{{ ($hasGst && count($gstGroups) > 0) ? 'margin-top:12px;' : '' }}">
                        <strong>Amount in Words:</strong> {{ $invoice['amount_in_words'] }}
                    </div>
                @endif
            </div>

            <div>
                <div class="summary-row"><span>Subtotal</span><span>₹{{ number_format($invoice['subtotal'], 2) }}</span></div>
                @if($hasDiscount)
                    <div class="summary-row"><span>Discount</span><span>₹{{ number_format($invoice['total_discount_amount'], 2) }}</span></div>
                @endif
                @if($hasGst)
                    <div class="summary-row"><span>Taxable</span><span>₹{{ number_format($invoice['taxable_amount'], 2) }}</span></div>
                    <div class="summary-row"><span>Tax</span><span>₹{{ number_format($invoice['gst_amount'], 2) }}</span></div>
                @endif
                @if($invoice['round_off'] != 0)
                    <div class="summary-row"><span>Round Off</span><span>₹{{ number_format($invoice['round_off'], 2) }}</span></div>
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
        </div>

        {{-- FOOTER --}}
        <div class="footer">
            <div class="footer-terms">
                @if($settings['show_terms'] && !empty($settings['terms_conditions']))
                    <strong>Terms & Conditions:</strong>
                    {{ $settings['terms_conditions'] }}
                @elseif(!empty($settings['footer_message']))
                    {{ $settings['footer_message'] }}
                @endif
            </div>
            @if($settings['show_signature'])
                <div class="sig-box">
                    @if(!empty($shop['signature']))
                        <img class="sig-image" src="{{ $shop['signature'] }}" alt="Signature">
                    @else
                        <div class="sig-line"></div>
                    @endif
                    <div class="sig-label">Authorized Signature</div>
                </div>
            @endif
        </div>

        {{-- SYSTEM FOOTER --}}
        <div class="sys-footer">
            <span>This is a system generated invoice</span>
            <span>Page 1 / 1</span>
        </div>

    </div>
</div>
</body>
</html>
