<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'sales';
    protected $guarded = [];
    protected $casts = [
        'invoice_date'               => 'datetime',
        'invoice_type'               => 'integer',
        'subtotal'                   => 'decimal:2',
        'item_discount_amount'       => 'decimal:2',
        'invoice_discount_type'      => 'integer',
        'invoice_discount_value'     => 'decimal:2',
        'invoice_discount_amount'    => 'decimal:2',
        'coupon_discount_amount'     => 'decimal:2',
        'promotion_discount_amount'  => 'decimal:2',
        'total_discount_amount'      => 'decimal:2',
        'taxable_amount'             => 'decimal:2',
        'cgst_amount'                => 'decimal:2',
        'sgst_amount'                => 'decimal:2',
        'igst_amount'                => 'decimal:2',
        'gst_amount'                 => 'decimal:2',
        'other_charges'              => 'decimal:2',
        'round_off'                  => 'decimal:2',
        'grand_total'                => 'decimal:2',
        'paid_amount'                => 'decimal:2',
        'due_amount'                 => 'decimal:2',
        'payment_status'             => 'integer',
        'sale_status'                => 'integer',
        'stock_status'               => 'integer',
        'status'                     => 'integer',
        'deleted'                    => 'integer',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function saleItems() { return $this->hasMany(SaleItem::class); }
    public function salePayments() { return $this->hasMany(SalePayment::class); }
    public function saleItemDiscounts() { return $this->hasMany(SaleItemDiscount::class); }
    public function saleInvoiceDiscounts() { return $this->hasMany(SaleInvoiceDiscount::class); }
    public function saleCreditNoteAdjustments() { return $this->hasMany(SaleCreditNoteAdjustment::class); }
}
