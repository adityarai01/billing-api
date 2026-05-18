<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SupplierCreditNoteAdjustment extends Model
{
    protected $table = 'supplier_credit_note_adjustments';
    protected $guarded = [];
    protected $casts = [
        'adjustment_date' => 'date',
        'adjusted_amount' => 'decimal:2',
        'adjustment_type' => 'integer',
    ];

    public function supplierCreditNote() { return $this->belongsTo(SupplierCreditNote::class); }
}
