<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionTarget extends Model
{
    protected $table = 'promotion_targets';
    protected $guarded = [];

    protected $casts = [
        'target_type' => 'integer',
        'target_id'   => 'integer',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
