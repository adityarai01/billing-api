<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $table = 'stock_adjustments';
    protected $guarded = [];
    protected $casts = [
        'adjustment_date' => 'date',
        'approved_at' => 'datetime',
        'adjustment_type' => 'integer',
        'reason_type' => 'integer',
        'approval_status' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function stockAdjustmentItems() { return $this->hasMany(StockAdjustmentItem::class); }
}
