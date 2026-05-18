<?php

namespace App\Services;

use App\Enums\InvoicePaperSizeEnum;
use App\Enums\InvoiceTemplateTypeEnum;
use App\Enums\OrganizationType;
use App\Enums\SalePaymentModeEnum;
use App\Enums\SalePaymentStatusEnum;
use App\Enums\SaleStatusEnum;
use App\Models\Organization;
use App\Models\OrganizationInvoiceSetting;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SaleInvoiceService
{
    public function getInvoiceData(int $organizationId, int $saleId, ?int $templateType = null): array
    {
        $sale = $this->loadSale($saleId, $organizationId);
        $settings = $this->getPrintSettings($organizationId, $sale);

        if ($templateType !== null) {
            $settings = $this->applyTemplateOverride($settings, $templateType);
        }

        return $this->buildInvoicePayload($sale, $settings);
    }

    public function getTemplateView(int $templateType): string
    {
        return (InvoiceTemplateTypeEnum::tryFrom($templateType) ?? InvoiceTemplateTypeEnum::A4GST)->view();
    }

    public function generatePdf(int $organizationId, int $saleId, ?int $templateType = null)
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new RuntimeException('DomPDF package is not installed. Run composer require barryvdh/laravel-dompdf first.');
        }

        $resolvedTemplateType = $templateType ?? InvoiceTemplateTypeEnum::A4GST->value;
        $data = $this->getInvoiceData($organizationId, $saleId, $resolvedTemplateType);
        $view = $this->getTemplateView($resolvedTemplateType);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);
    }

    public function generateAndStorePdf(int $saleId, ?int $templateType = null): ?string
    {
        $sale = $this->loadSale($saleId);

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            Log::warning('Sale invoice PDF skipped because DomPDF is unavailable.', [
                'sale_id' => $saleId,
            ]);

            return null;
        }

        $settings = $this->getPrintSettings((int) $sale->organization_id, $sale);
        $resolvedTemplateType = $templateType ?? (int) ($settings['template_type'] ?? InvoiceTemplateTypeEnum::A4GST->value);
        $pdf = $this->generatePdf((int) $sale->organization_id, (int) $sale->id, $resolvedTemplateType);

        $path = sprintf(
            'invoices/%d/%s-%d.pdf',
            $sale->organization_id,
            Str::slug((string) $sale->invoice_no, '-'),
            $resolvedTemplateType
        );

        Storage::disk('public')->put($path, $pdf->output());

        $sale->forceFill([
            'invoice_pdf_path' => $path,
        ])->save();

        return $path;
    }

    public function calculateInvoiceSummary($sale): array
    {
        $items = $sale->saleItems instanceof Collection
            ? $sale->saleItems
            : collect($sale->saleItems ?? []);
        $payments = $sale->salePayments instanceof Collection
            ? $sale->salePayments
            : collect($sale->salePayments ?? []);

        return [
            'item_count' => $items->count(),
            'total_qty' => round((float) $items->sum('qty'), 3),
            'subtotal' => $this->money($sale->subtotal),
            'item_discount_amount' => $this->money($sale->item_discount_amount),
            'invoice_discount_amount' => $this->money($sale->invoice_discount_amount),
            'coupon_discount_amount' => $this->money($sale->coupon_discount_amount),
            'promotion_discount_amount' => $this->money($sale->promotion_discount_amount),
            'total_discount_amount' => $this->money($sale->total_discount_amount),
            'taxable_amount' => $this->money($sale->taxable_amount),
            'cgst_amount' => $this->money($sale->cgst_amount),
            'sgst_amount' => $this->money($sale->sgst_amount),
            'igst_amount' => $this->money($sale->igst_amount),
            'gst_amount' => $this->money($sale->gst_amount),
            'other_charges' => $this->money($sale->other_charges),
            'round_off' => $this->money($sale->round_off),
            'grand_total' => $this->money($sale->grand_total),
            'paid_amount' => $this->money($sale->paid_amount),
            'due_amount' => $this->money($sale->due_amount),
            'payment_modes_summary' => $this->buildPaymentSummary($payments),
        ];
    }

    public function getPrintSettings(int $organizationId, ?Sale $sale = null): array
    {
        $organization = $sale?->organization;
        if (!$organization) {
            $organization = Organization::with('invoiceSetting')
                ->where('deleted', 0)
                ->findOrFail($organizationId);
        } elseif (!$organization->relationLoaded('invoiceSetting')) {
            $organization->load('invoiceSetting');
        }

        $settings = $organization->invoiceSetting;
        $shopType = $organization->shop_type instanceof OrganizationType
            ? $organization->shop_type
            : OrganizationType::tryFrom((int) $organization->shop_type);
        $hasBatchData = $sale?->saleItems?->contains(fn($item) => filled($item->batch_no) || $item->batch_id) ?? false;
        $hasGstProfile = filled($organization->gstin) || (($sale ? (float) $sale->gst_amount : 0.0) > 0);

        $defaultTemplate = match ($shopType) {
            OrganizationType::Medical => InvoiceTemplateTypeEnum::A4GST,
            OrganizationType::Cloth => InvoiceTemplateTypeEnum::SimpleRetail,
            OrganizationType::Grocery, OrganizationType::General, null => InvoiceTemplateTypeEnum::Thermal80mm,
        };

        $template = InvoiceTemplateTypeEnum::tryFrom((int) ($settings?->invoice_template ?? $defaultTemplate->value))
            ?? $defaultTemplate;
        $paperSize = InvoicePaperSizeEnum::tryFrom((int) ($settings?->thermal_paper_size ?? $template->paperSize()->value))
            ?? $template->paperSize();

        $showBatchDefault = match ($shopType) {
            OrganizationType::Medical => true,
            OrganizationType::Grocery, OrganizationType::General => $hasBatchData,
            OrganizationType::Cloth, null => false,
        };

        $showExpiryDefault = match ($shopType) {
            OrganizationType::Medical => true,
            OrganizationType::Grocery, OrganizationType::General => $hasBatchData,
            OrganizationType::Cloth, null => false,
        };

        $resolved = [
            'invoice_prefix' => $organization->invoice_prefix ?: 'INV',
            'invoice_start_no' => max(1, (int) ($organization->invoice_start_no ?: 1)),
            'template' => $template->slug(),
            'template_type' => $template->value,
            'template_label' => $template->label(),
            'thermal_paper_size' => $paperSize->slug(),
            'thermal_paper_size_value' => $paperSize->value,
            'thermal_paper_size_label' => $paperSize->label(),
            'print_after_sale' => (bool) ($settings?->print_after_sale ?? false),
            'show_logo' => (bool) ($settings?->show_logo_on_invoice ?? true),
            'show_gst' => (bool) ($settings?->show_gst_on_invoice ?? $hasGstProfile) && $hasGstProfile,
            'show_discount' => (bool) ($settings?->show_discount_on_invoice ?? true),
            'show_hsn' => (bool) ($settings?->show_hsn_on_invoice ?? $hasGstProfile),
            'show_batch' => (bool) ($settings?->show_batch_on_invoice ?? $showBatchDefault),
            'show_expiry' => (bool) ($settings?->show_expiry_on_invoice ?? $showExpiryDefault),
            'show_terms' => (bool) ($settings?->show_terms_on_invoice ?? false),
            'show_signature' => (bool) ($settings?->show_signature_on_invoice ?? false),
            'footer_message' => $settings?->invoice_footer_message ?: 'Thank You! Visit Again',
            'terms_conditions' => $settings?->terms_conditions,
        ];

        if ($sale) {
            $resolved['show_discount'] = $resolved['show_discount'] || ((float) $sale->total_discount_amount > 0);
            $resolved['show_batch'] = $resolved['show_batch'] && $hasBatchData;
            $resolved['show_expiry'] = $resolved['show_expiry'] && $hasBatchData;
            $resolved['terms_conditions'] = $sale->terms_conditions ?: $resolved['terms_conditions'];
        }

        return $resolved;
    }

    public function updatePrintSettings(int $organizationId, array $payload): array
    {
        $organization = Organization::where('deleted', 0)->findOrFail($organizationId);
        $current = $this->getPrintSettings($organizationId);

        $organization->fill([
            'invoice_prefix' => $payload['invoice_prefix'] ?? $organization->invoice_prefix,
            'invoice_start_no' => $payload['invoice_start_no'] ?? $organization->invoice_start_no,
        ])->save();

        OrganizationInvoiceSetting::updateOrCreate(
            ['organization_id' => $organizationId],
            [
                'invoice_template' => $payload['invoice_template'] ?? $current['template_type'],
                'thermal_paper_size' => $payload['thermal_paper_size'] ?? $current['thermal_paper_size_value'],
                'print_after_sale' => (bool) ($payload['print_after_sale'] ?? $current['print_after_sale']),
                'show_logo_on_invoice' => (bool) ($payload['show_logo_on_invoice'] ?? $current['show_logo']),
                'show_gst_on_invoice' => (bool) ($payload['show_gst_on_invoice'] ?? $current['show_gst']),
                'show_discount_on_invoice' => (bool) ($payload['show_discount_on_invoice'] ?? $current['show_discount']),
                'show_hsn_on_invoice' => (bool) ($payload['show_hsn_on_invoice'] ?? $current['show_hsn']),
                'show_batch_on_invoice' => (bool) ($payload['show_batch_on_invoice'] ?? $current['show_batch']),
                'show_expiry_on_invoice' => (bool) ($payload['show_expiry_on_invoice'] ?? $current['show_expiry']),
                'show_terms_on_invoice' => (bool) ($payload['show_terms_on_invoice'] ?? $current['show_terms']),
                'show_signature_on_invoice' => (bool) ($payload['show_signature_on_invoice'] ?? $current['show_signature']),
                'terms_conditions' => array_key_exists('terms_conditions', $payload) ? $payload['terms_conditions'] : $current['terms_conditions'],
                'invoice_footer_message' => array_key_exists('invoice_footer_message', $payload) ? $payload['invoice_footer_message'] : $current['footer_message'],
            ]
        );

        return $this->getPrintSettings($organizationId);
    }

    public function prepareThermalData(int $organizationId, int $saleId, int $paperSize): array
    {
        $templateType = $paperSize === InvoicePaperSizeEnum::Thermal58mm->value
            ? InvoiceTemplateTypeEnum::Thermal58mm->value
            : InvoiceTemplateTypeEnum::Thermal80mm->value;

        return $this->getInvoiceData($organizationId, $saleId, $templateType);
    }

    public function prepareA4Data(int $organizationId, int $saleId): array
    {
        return $this->getInvoiceData($organizationId, $saleId, InvoiceTemplateTypeEnum::A4GST->value);
    }

    public function generateThermalInvoice(int $organizationId, int $saleId): array
    {
        $settings = $this->getPrintSettings($organizationId, $this->loadSale($saleId, $organizationId));

        return $this->prepareThermalData(
            $organizationId,
            $saleId,
            (int) ($settings['thermal_paper_size_value'] ?? InvoicePaperSizeEnum::Thermal80mm->value)
        );
    }

    public function generateA4Invoice(int $organizationId, int $saleId): array
    {
        return $this->prepareA4Data($organizationId, $saleId);
    }

    public function prepareSendPayload(int $saleId, string $channel, ?int $templateType = null): array
    {
        $sale = $this->loadSale($saleId);
        $pdfPath = $sale->invoice_pdf_path;

        if (!$pdfPath && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdfPath = $this->generateAndStorePdf($saleId, $templateType);
            $sale->refresh();
        }

        return [
            'sale_id' => (int) $sale->id,
            'organization_id' => (int) $sale->organization_id,
            'invoice_no' => $sale->invoice_no,
            'channel' => $channel,
            'customer_name' => $sale->customer?->name,
            'customer_mobile_no' => $sale->customer?->mobile_no,
            'customer_email' => $sale->customer?->email,
            'pdf_path' => $pdfPath ?: $sale->invoice_pdf_path,
            'pdf_url' => ($pdfPath ?: $sale->invoice_pdf_path) ? $this->normalizeMediaPath($pdfPath ?: $sale->invoice_pdf_path) : null,
            'ready' => filled($pdfPath ?: $sale->invoice_pdf_path),
        ];
    }

    public function getAmountInWords(float $amount): string
    {
        $amount = round($amount, 2);
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $rupeesWords = $this->spellOut($rupees);
        $result = trim($rupeesWords . ' Rupees');

        if ($paise > 0) {
            $result .= ' and ' . $this->spellOut($paise) . ' Paise';
        }

        return $result . ' Only';
    }

    private function buildInvoicePayload(Sale $sale, array $settings): array
    {
        $summary = $this->calculateInvoiceSummary($sale);
        $shop = $sale->organization;
        $items = $sale->saleItems->values()->map(function (SaleItem $item, int $index) use ($sale) {
            $product = $item->product;
            $variant = $item->productVariant;
            $batch = $item->productBatch;
            $variantAttributes = $variant?->attributeValues?->map(function ($attributeValue) {
                return [
                    'name' => $attributeValue->attribute?->name,
                    'value' => $attributeValue->attributeValue?->value,
                ];
            })->filter(fn($value) => filled($value['name']) && filled($value['value']))->values()->all() ?? [];

            $attributeSummary = collect($variantAttributes)
                ->map(fn(array $attribute) => $attribute['name'] . ': ' . $attribute['value'])
                ->implode(', ');

            return [
                'sr_no' => $index + 1,
                'product_name' => $item->product_name ?: $product?->name,
                'variant_name' => $item->variant_name ?: $variant?->variant_name,
                'display_name' => $this->resolveItemDisplayName($sale->organization, $item, $attributeSummary),
                'variant_attributes' => $variantAttributes,
                'attribute_summary' => $attributeSummary,
                'hsn_code' => $product?->hsn_code,
                'batch_no' => $item->batch_no ?: $batch?->batch_no,
                'expiry_date' => $batch?->expiry_date?->format('Y-m'),
                'expiry_display' => $batch?->expiry_date?->format('m/y'),
                'qty' => $this->quantity($item->qty),
                'mrp' => $this->money($item->mrp),
                'unit_price' => $this->money($item->unit_price),
                'gross_amount' => $this->money($item->gross_amount),
                'discount_amount' => $this->money($item->discount_amount),
                'promotion_discount_amount' => $this->money($item->promotion_discount_amount),
                'total_discount_amount' => $this->money($item->total_discount_amount),
                'taxable_amount' => $this->money($item->taxable_amount),
                'gst_percent' => $this->money($item->gst_percent),
                'cgst_percent' => $this->money($item->cgst_percent),
                'sgst_percent' => $this->money($item->sgst_percent),
                'igst_percent' => $this->money($item->igst_percent),
                'cgst_amount' => $this->money($item->cgst_amount),
                'sgst_amount' => $this->money($item->sgst_amount),
                'igst_amount' => $this->money($item->igst_amount),
                'gst_amount' => $this->money($item->gst_amount),
                'total_amount' => $this->money($item->total_amount),
                'is_free_item' => (bool) $item->is_free_item,
            ];
        })->all();

        $payments = $sale->salePayments->values()->map(function ($payment) {
            return [
                'payment_mode' => SalePaymentModeEnum::tryFrom((int) $payment->payment_mode)?->label() ?? 'Unknown',
                'amount' => $this->money($payment->amount),
                'reference_no' => $payment->reference_no,
                'payment_date' => $payment->payment_date?->format('Y-m-d H:i:s'),
            ];
        })->all();

        $invoiceDiscounts = $sale->saleInvoiceDiscounts->values()->map(function ($discount) {
            return [
                'source' => (int) $discount->discount_source,
                'coupon_code' => $discount->coupon_code,
                'discount_type' => $discount->discount_type,
                'discount_value' => $this->money($discount->discount_value),
                'discount_amount' => $this->money($discount->discount_amount),
                'remarks' => $discount->remarks,
            ];
        })->all();

        $creditNoteAdjustments = $sale->saleCreditNoteAdjustments->values()->map(function ($adjustment) {
            return [
                'credit_note_id' => $adjustment->credit_note_id,
                'adjusted_amount' => $this->money($adjustment->adjusted_amount ?? 0),
                'remarks' => $adjustment->remarks ?? null,
            ];
        })->all();

        return [
            'shop' => [
                'id' => (int) $shop->id,
                'name' => $shop->shop_name,
                'business_name' => $shop->business_name,
                'logo' => $this->normalizeMediaPath($shop->logo),
                'signature' => $this->normalizeMediaPath($shop->signature),
                'address' => $this->formatAddress($shop->address, $shop->city, $shop->state, $shop->pincode),
                'mobile_no' => $shop->mobile_no,
                'email' => $shop->email,
                'gstin' => $shop->gstin,
                'state' => $shop->state,
                'state_code' => $shop->state_code,
                'country' => $shop->country,
                'shop_type' => $shop->shop_type instanceof OrganizationType ? $shop->shop_type->label() : null,
            ],
            'invoice' => [
                'id' => (int) $sale->id,
                'invoice_no' => $sale->invoice_no,
                'invoice_date' => $sale->invoice_date?->format('Y-m-d H:i:s'),
                'invoice_date_display' => $sale->invoice_date?->format('d-m-Y h:i A'),
                'invoice_date_short' => $sale->invoice_date?->format('d-m-Y'),
                'invoice_type' => $this->getInvoiceTypeLabel((int) $sale->invoice_type),
                'subtotal' => $summary['subtotal'],
                'item_discount_amount' => $summary['item_discount_amount'],
                'invoice_discount_amount' => $summary['invoice_discount_amount'],
                'coupon_discount_amount' => $summary['coupon_discount_amount'],
                'promotion_discount_amount' => $summary['promotion_discount_amount'],
                'total_discount_amount' => $summary['total_discount_amount'],
                'taxable_amount' => $summary['taxable_amount'],
                'cgst_amount' => $summary['cgst_amount'],
                'sgst_amount' => $summary['sgst_amount'],
                'igst_amount' => $summary['igst_amount'],
                'gst_amount' => $summary['gst_amount'],
                'other_charges' => $summary['other_charges'],
                'round_off' => $summary['round_off'],
                'grand_total' => $summary['grand_total'],
                'paid_amount' => $summary['paid_amount'],
                'due_amount' => $summary['due_amount'],
                'amount_in_words' => $this->getAmountInWords((float) $sale->grand_total),
                'payment_status' => SalePaymentStatusEnum::tryFrom((int) $sale->payment_status)?->label() ?? 'Unknown',
                'sale_status' => SaleStatusEnum::tryFrom((int) $sale->sale_status)?->label() ?? 'Unknown',
                'is_cancelled' => (int) $sale->sale_status === SaleStatusEnum::Cancelled->value,
                'notes' => $sale->notes,
            ],
            'customer' => [
                'name' => $sale->customer?->name ?: 'Walk-in Customer',
                'mobile_no' => $sale->customer?->mobile_no,
                'address' => $sale->customer?->address,
                'gstin' => $sale->customer?->gstin,
                'email' => $sale->customer?->email,
            ],
            'items' => $items,
            'payments' => $payments,
            'settings' => $settings,
            'summary' => $summary,
            'invoice_discounts' => $invoiceDiscounts,
            'credit_note_adjustments' => $creditNoteAdjustments,
            'template_view' => $this->getTemplateView((int) $settings['template_type']),
            'is_cancelled' => (int) $sale->sale_status === SaleStatusEnum::Cancelled->value,
        ];
    }

    private function loadSale(int $saleId, ?int $organizationId = null): Sale
    {
        return Sale::query()
            ->where('id', $saleId)
            ->where('deleted', 0)
            ->when($organizationId !== null, fn($query) => $query->where('organization_id', $organizationId))
            ->with([
                'customer',
                'organization.invoiceSetting',
                'saleItems' => fn($query) => $query
                    ->where('deleted', 0)
                    ->with([
                        'product:id,name,hsn_code,gst_percent,image',
                        'productVariant.attributeValues.attribute:id,name',
                        'productVariant.attributeValues.attributeValue:id,value',
                        'productBatch:id,batch_no,expiry_date,mrp,selling_price',
                    ])
                    ->orderBy('id'),
                'salePayments' => fn($query) => $query->where('deleted', 0)->orderBy('id'),
                'saleInvoiceDiscounts',
                'saleCreditNoteAdjustments',
            ])
            ->firstOrFail();
    }

    private function applyTemplateOverride(array $settings, int $templateType): array
    {
        $template = InvoiceTemplateTypeEnum::tryFrom($templateType) ?? InvoiceTemplateTypeEnum::A4GST;
        $paperSize = $template->paperSize();

        $settings['template'] = $template->slug();
        $settings['template_type'] = $template->value;
        $settings['template_label'] = $template->label();
        $settings['thermal_paper_size'] = $paperSize->slug();
        $settings['thermal_paper_size_value'] = $paperSize->value;
        $settings['thermal_paper_size_label'] = $paperSize->label();

        return $settings;
    }

    private function normalizeMediaPath(?string $path): ?string
    {
        if (!filled($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return url('/' . ltrim($path, '/'));
        }

        return Storage::disk('public')->url($path);
    }

    private function formatAddress(?string ...$parts): ?string
    {
        $filtered = array_values(array_filter($parts, fn($part) => filled($part)));

        return empty($filtered) ? null : implode(', ', $filtered);
    }

    private function resolveItemDisplayName(Organization $organization, SaleItem $item, string $attributeSummary = ''): string
    {
        $shopType = $organization->shop_type instanceof OrganizationType
            ? $organization->shop_type
            : OrganizationType::tryFrom((int) $organization->shop_type);

        $productName = $item->product_name ?: $item->product?->name ?: 'Item';
        $variantName = $item->variant_name ?: $item->productVariant?->variant_name;
        $displayName = $variantName ?: $productName;

        if ($shopType === OrganizationType::Cloth && filled($attributeSummary)) {
            return $displayName . ' (' . $attributeSummary . ')';
        }

        if ($shopType === OrganizationType::Medical && filled($variantName)) {
            return $variantName;
        }

        if ($displayName === $productName && filled($attributeSummary)) {
            return $displayName . ' (' . $attributeSummary . ')';
        }

        return $displayName;
    }

    private function buildPaymentSummary(Collection $payments): string
    {
        $modes = $payments
            ->map(fn($payment) => SalePaymentModeEnum::tryFrom((int) $payment->payment_mode)?->label())
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($modes) ? 'N/A' : implode(' + ', $modes);
    }

    private function getInvoiceTypeLabel(int $invoiceType): string
    {
        return match ($invoiceType) {
            1 => 'POS',
            2 => 'Tax Invoice',
            3 => 'Estimate',
            4 => 'Proforma',
            default => 'Invoice',
        };
    }

    private function spellOut(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
            $formatted = (string) $formatter->format($number);

            return Str::of($formatted)->replace('-', ' ')->title()->value();
        }

        $ones = [
            0 => '',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
        ];
        $tens = [
            2 => 'Twenty',
            3 => 'Thirty',
            4 => 'Forty',
            5 => 'Fifty',
            6 => 'Sixty',
            7 => 'Seventy',
            8 => 'Eighty',
            9 => 'Ninety',
        ];

        $segments = [
            10000000 => 'Crore',
            100000 => 'Lakh',
            1000 => 'Thousand',
            100 => 'Hundred',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $rest = $number % 10;

            return trim($tens[$ten] . ' ' . $this->spellOut($rest));
        }

        foreach ($segments as $divisor => $label) {
            if ($number >= $divisor) {
                $quotient = intdiv($number, $divisor);
                $remainder = $number % $divisor;
                $prefix = $this->spellOut($quotient) . ' ' . $label;

                return trim($prefix . ' ' . $this->spellOut($remainder));
            }
        }

        return '';
    }

    private function money($value): float
    {
        return round((float) $value, 2);
    }

    private function quantity($value): int|float
    {
        $qty = round((float) $value, 3);

        return abs($qty - round($qty)) < 0.0001 ? (int) round($qty) : $qty;
    }
}
