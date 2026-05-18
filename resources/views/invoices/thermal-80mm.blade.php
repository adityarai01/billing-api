<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice['invoice_no'] }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                width: 80mm;
                font-size: 11px;
                font-family: Arial, sans-serif;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            margin: 0;
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
        }

        .thermal-80 {
            width: 80mm;
            padding: 8px 7px 10px;
            box-sizing: border-box;
            font-size: 11px;
            position: relative;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #444; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        .shop-name { font-size: 14px; font-weight: 700; text-transform: uppercase; }
        .meta-row, .summary-row { display: flex; justify-content: space-between; gap: 8px; }
        .summary-row strong { font-size: 12px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 2px 0; vertical-align: top; }
        th { font-size: 10px; text-transform: uppercase; text-align: left; }
        .qty, .rate, .amount { text-align: right; white-space: nowrap; }
        .item-name { width: 48%; word-break: break-word; }
        .batch-row td { font-size: 10px; color: #444; padding-top: 0; }
        .cancelled-watermark {
            position: absolute;
            top: 42%;
            left: 9%;
            transform: rotate(-25deg);
            font-size: 44px;
            color: rgba(220, 38, 38, 0.14);
            font-weight: 700;
            z-index: 0;
            pointer-events: none;
        }
        .content { position: relative; z-index: 1; }
    </style>
</head>
<body>
@php
    $showDiscountBreakup = $settings['show_discount'] && ($invoice['total_discount_amount'] > 0 || $invoice['item_discount_amount'] > 0 || $invoice['invoice_discount_amount'] > 0);
@endphp
<div class="thermal-80 invoice-print-area">
    @if($is_cancelled)
        <div class="cancelled-watermark">CANCELLED</div>
    @endif

    <div class="content">
        <div class="center">
            @if($settings['show_logo'] && !empty($shop['logo']))
                <div style="margin-bottom: 4px;">
                    <img src="{{ $shop['logo'] }}" alt="Logo" style="max-width: 52px; max-height: 40px;">
                </div>
            @endif
            <div class="shop-name">{{ $shop['name'] }}</div>
            @if(!empty($shop['business_name']) && $shop['business_name'] !== $shop['name'])
                <div>{{ $shop['business_name'] }}</div>
            @endif
            @if(!empty($shop['address']))
                <div>{{ $shop['address'] }}</div>
            @endif
            <div>Mob: {{ $shop['mobile_no'] ?? 'N/A' }}</div>
            @if($settings['show_gst'] && !empty($shop['gstin']))
                <div>GSTIN: {{ $shop['gstin'] }}</div>
            @endif
        </div>

        <div class="line"></div>

        <div class="meta-row"><span>Invoice:</span><span>{{ $invoice['invoice_no'] }}</span></div>
        <div class="meta-row"><span>Date:</span><span>{{ $invoice['invoice_date_display'] }}</span></div>
        <div class="meta-row"><span>Customer:</span><span>{{ $customer['name'] }}</span></div>
        @if(!empty($customer['mobile_no']))
            <div class="meta-row"><span>Mobile:</span><span>{{ $customer['mobile_no'] }}</span></div>
        @endif
        <div class="meta-row"><span>Status:</span><span>{{ $invoice['sale_status'] }}</span></div>

        <div class="line"></div>

        <table>
            <thead>
            <tr>
                <th class="item-name">Item</th>
                <th class="qty">Qty</th>
                <th class="rate">Rate</th>
                <th class="amount">Amt</th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="item-name">{{ $item['display_name'] }}</td>
                    <td class="qty">{{ $item['qty'] }}</td>
                    <td class="rate">{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="amount">{{ number_format($item['gross_amount'], 2) }}</td>
                </tr>
                @if($settings['show_batch'] && !empty($item['batch_no']))
                    <tr class="batch-row">
                        <td colspan="4">
                            Batch: {{ $item['batch_no'] }}
                            @if($settings['show_expiry'] && !empty($item['expiry_display']))
                                &nbsp; Exp: {{ $item['expiry_display'] }}
                            @endif
                        </td>
                    </tr>
                @endif
                @if($showDiscountBreakup && $item['discount_amount'] > 0)
                    <tr class="batch-row">
                        <td colspan="4">Disc: {{ number_format($item['discount_amount'], 2) }}</td>
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table>

        <div class="line"></div>

        <div class="summary-row"><span>Subtotal</span><span>{{ number_format($invoice['subtotal'], 2) }}</span></div>
        @if($showDiscountBreakup)
            <div class="summary-row"><span>Item Discount</span><span>{{ number_format($invoice['item_discount_amount'], 2) }}</span></div>
            <div class="summary-row"><span>Bill Discount</span><span>{{ number_format($invoice['invoice_discount_amount'], 2) }}</span></div>
            @if($invoice['coupon_discount_amount'] > 0)
                <div class="summary-row"><span>Coupon Discount</span><span>{{ number_format($invoice['coupon_discount_amount'], 2) }}</span></div>
            @endif
        @endif
        @if($settings['show_gst'])
            <div class="summary-row"><span>GST</span><span>{{ number_format($invoice['gst_amount'], 2) }}</span></div>
        @endif
        <div class="summary-row"><span>Round Off</span><span>{{ number_format($invoice['round_off'], 2) }}</span></div>
        <div class="summary-row"><strong>Grand Total</strong><strong>{{ number_format($invoice['grand_total'], 2) }}</strong></div>
        <div class="summary-row"><span>Paid</span><span>{{ number_format($invoice['paid_amount'], 2) }}</span></div>
        <div class="summary-row"><span>Due</span><span>{{ number_format($invoice['due_amount'], 2) }}</span></div>

        <div class="line"></div>

        <div>Payment: {{ $summary['payment_modes_summary'] }}</div>
        @if(!empty($settings['footer_message']))
            <div class="center" style="margin-top: 8px;">{{ $settings['footer_message'] }}</div>
        @endif
    </div>
</div>
</body>
</html>
