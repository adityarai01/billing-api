<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DebitNoteAdjustment extends Model
{
    protected $table = 'debit_note_adjustments';
    protected $guarded = [];
    protected $casts = [
        'adjustment_date' => 'date',
        'adjusted_amount' => 'decimal:2',
        'adjustment_type' => 'integer',
    ];

    public function debitNote() { return $this->belongsTo(DebitNote::class); }
}
