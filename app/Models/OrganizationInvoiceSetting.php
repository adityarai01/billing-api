<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationInvoiceSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'invoice_template',
        'thermal_paper_size',
        'print_after_sale',
        'show_logo_on_invoice',
        'show_gst_on_invoice',
        'show_discount_on_invoice',
        'show_hsn_on_invoice',
        'show_batch_on_invoice',
        'show_expiry_on_invoice',
        'show_terms_on_invoice',
        'show_signature_on_invoice',
        'terms_conditions',
        'invoice_footer_message',
    ];

    protected $casts = [
        'invoice_template' => 'integer',
        'thermal_paper_size' => 'integer',
        'print_after_sale' => 'boolean',
        'show_logo_on_invoice' => 'boolean',
        'show_gst_on_invoice' => 'boolean',
        'show_discount_on_invoice' => 'boolean',
        'show_hsn_on_invoice' => 'boolean',
        'show_batch_on_invoice' => 'boolean',
        'show_expiry_on_invoice' => 'boolean',
        'show_terms_on_invoice' => 'boolean',
        'show_signature_on_invoice' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
