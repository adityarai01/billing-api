<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleCalculateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'             => ['nullable', 'integer'],
            'coupon_code'             => ['nullable', 'string', 'max:50'],
            'invoice_discount_type'   => ['nullable', Rule::in([1, 2])],
            'invoice_discount_value'  => ['nullable', 'numeric', 'min:0'],
            'other_charges'           => ['nullable', 'numeric', 'min:0'],
            'round_off'               => ['nullable', 'numeric'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer'],
            'items.*.product_variant_id' => ['required', 'integer'],
            'items.*.batch_id'        => ['nullable', 'integer'],
            'items.*.qty'             => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'      => ['nullable', 'numeric', 'min:0'],
            'items.*.mrp'             => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type'   => ['nullable', Rule::in([1, 2])],
            'items.*.discount_value'  => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_percent'     => ['nullable', 'numeric', 'min:0'],
            'items.*.purchase_price'  => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
