<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SaleInvoiceDiscount extends Model
{
    protected $table = 'sale_invoice_discounts';
    protected $guarded = [];
    protected $casts = [
        'discount_source' => 'integer',
        'discount_type'   => 'integer',
        'discount_value'  => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
}
