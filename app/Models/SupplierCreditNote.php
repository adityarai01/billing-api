<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SupplierCreditNote extends Model
{
    protected $table = 'supplier_credit_notes';
    protected $guarded = [];
    protected $casts = [
        'credit_note_date' => 'date',
        'total_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'credit_note_type' => 'integer',
        'credit_status' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function supplierCreditNoteAdjustments() { return $this->hasMany(SupplierCreditNoteAdjustment::class); }
}
