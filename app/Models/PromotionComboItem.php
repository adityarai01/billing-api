<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionComboItem extends Model
{
    protected $table = 'promotion_combo_items';
    protected $guarded = [];

    protected $casts = [
        'product_id'         => 'integer',
        'product_variant_id' => 'integer',
        'category_id'        => 'integer',
        'brand_id'           => 'integer',
        'required_qty'       => 'decimal:3',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
