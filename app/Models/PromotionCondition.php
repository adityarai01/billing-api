<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionCondition extends Model
{
    protected $table = 'promotion_conditions';
    protected $guarded = [];

    protected $casts = [
        'condition_type' => 'integer',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
