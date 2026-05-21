<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionBuyGetRule extends Model
{
    protected $table = 'promotion_buy_get_rules';
    protected $guarded = [];

    protected $casts = [
        'buy_qty'             => 'decimal:3',
        'get_qty'             => 'decimal:3',
        'buy_target_type'     => 'integer',
        'buy_target_id'       => 'integer',
        'get_target_type'     => 'integer',
        'get_target_id'       => 'integer',
        'is_same_product'     => 'integer',
        'max_free_qty'        => 'decimal:3',
        'auto_add_free_item'  => 'integer',
        'allow_cashier_select' => 'integer',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
