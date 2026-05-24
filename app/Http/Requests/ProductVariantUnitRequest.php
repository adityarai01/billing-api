<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductVariantUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'               => ['required', 'integer', 'exists:products,id'],
            'product_variant_id'       => ['required', 'integer', 'exists:product_variants,id'],
            'unit_id'                  => ['required', 'integer', 'exists:units,id'],
            'conversion_qty'           => ['required', 'numeric', 'min:0.001'],
            'purchase_price'           => ['nullable', 'numeric', 'min:0'],
            'mrp'                      => ['nullable', 'numeric', 'min:0'],
            'selling_price'            => ['nullable', 'numeric', 'min:0'],
            'wholesale_price'          => ['nullable', 'numeric', 'min:0'],
            'barcode'                  => ['nullable', 'string', 'max:100'],
            'is_base_unit'             => ['nullable', 'in:0,1'],
            'is_default_purchase_unit' => ['nullable', 'in:0,1'],
            'is_default_sale_unit'     => ['nullable', 'in:0,1'],
            'status'                   => ['nullable', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'conversion_qty.min' => 'Conversion quantity must be at least 0.001.',
            'unit_id.required'   => 'Please select a unit.',
        ];
    }
}
