<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SaleCreditNoteAdjustment extends Model
{
    protected $table = 'sale_credit_note_adjustments';
    protected $guarded = [];
    protected $casts = [
        'adjusted_amount'  => 'decimal:2',
        'adjustment_date'  => 'datetime',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function creditNote() { return $this->belongsTo(SupplierCreditNote::class, 'credit_note_id'); }
}
