<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    protected $table = 'debit_notes';
    protected $guarded = [];
    protected $casts = [
        'debit_note_date' => 'date',
        'total_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'debit_note_type' => 'integer',
        'debit_status' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function purchaseReturn() { return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id'); }
    public function debitNoteAdjustments() { return $this->hasMany(DebitNoteAdjustment::class); }
}
