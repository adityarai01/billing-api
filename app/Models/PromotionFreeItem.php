<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionFreeItem extends Model
{
    protected $table = 'promotion_free_items';
    protected $guarded = [];

    protected $casts = [
        'product_id'         => 'integer',
        'product_variant_id' => 'integer',
        'category_id'        => 'integer',
        'brand_id'           => 'integer',
        'free_qty'           => 'decimal:3',
        'allow_selection'    => 'integer',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
