<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice['invoice_no'] }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        @media print {
            body {
                margin: 0;
                width: 58mm;
                font-size: 10px;
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

        .thermal-58 {
            width: 58mm;
            padding: 7px 6px 9px;
            box-sizing: border-box;
            font-size: 10px;
            position: relative;
        }

        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 4px 0; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .shop-name { font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .item { margin-bottom: 4px; }
        .item-name { font-weight: 600; word-break: break-word; }
        .muted { color: #444; }
        .cancelled-watermark {
            position: absolute;
            top: 42%;
            left: 2%;
            transform: rotate(-23deg);
            font-size: 30px;
            color: rgba(220, 38, 38, 0.13);
            font-weight: 700;
            pointer-events: none;
        }
        .content { position: relative; z-index: 1; }
    </style>
</head>
<body>
<div class="thermal-58 invoice-print-area">
    @if($is_cancelled)
        <div class="cancelled-watermark">CANCELLED</div>
    @endif

    <div class="content">
        <div class="center">
            <div class="shop-name">{{ $shop['name'] }}</div>
            <div>Mob: {{ $shop['mobile_no'] ?? 'N/A' }}</div>
        </div>

        <div class="line"></div>
        <div class="row"><span>Inv:</span><span>{{ $invoice['invoice_no'] }}</span></div>
        <div class="row"><span>Date:</span><span>{{ $invoice['invoice_date_short'] }}</span></div>
        @if($customer['name'] !== 'Walk-in Customer')
            <div class="row"><span>Cust:</span><span>{{ $customer['name'] }}</span></div>
        @endif

        <div class="line"></div>

        @foreach($items as $item)
            <div class="item">
                <div class="item-name">{{ $item['display_name'] }}</div>
                <div>{{ $item['qty'] }} x {{ number_format($item['unit_price'], 2) }} = {{ number_format($item['total_amount'], 2) }}</div>
                @if($settings['show_batch'] && !empty($item['batch_no']))
                    <div class="muted">
                        {{ $item['batch_no'] }}
                        @if($settings['show_expiry'] && !empty($item['expiry_display']))
                            / {{ $item['expiry_display'] }}
                        @endif
                    </div>
                @endif
            </div>
        @endforeach

        <div class="line"></div>
        <div class="row"><span>Total</span><span>{{ number_format($invoice['grand_total'], 2) }}</span></div>
        <div class="row"><span>Paid</span><span>{{ number_format($invoice['paid_amount'], 2) }}</span></div>
        <div class="row"><span>Due</span><span>{{ number_format($invoice['due_amount'], 2) }}</span></div>
        <div class="row"><span>Pay</span><span>{{ $summary['payment_modes_summary'] }}</span></div>

        @if(!empty($settings['footer_message']))
            <div class="line"></div>
            <div class="center">{{ $settings['footer_message'] }}</div>
        @endif
    </div>
</div>
</body>
</html>
