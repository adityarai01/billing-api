<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'                       => ['nullable', 'integer'],
            'invoice_date'                      => ['required', 'date'],
            'invoice_type'                      => ['nullable', Rule::in([1, 2, 3, 4])],
            'invoice_discount_type'             => ['nullable', Rule::in([1, 2])],
            'invoice_discount_value'            => ['nullable', 'numeric', 'min:0'],
            'coupon_code'                       => ['nullable', 'string', 'max:50'],
            'other_charges'                     => ['nullable', 'numeric', 'min:0'],
            'round_off'                         => ['nullable', 'numeric'],
            'notes'                             => ['nullable', 'string'],
            'terms_conditions'                  => ['nullable', 'string'],
            'items'                             => ['required', 'array', 'min:1'],
            'items.*.product_id'                => ['required', 'integer'],
            'items.*.product_variant_id'        => ['required', 'integer'],
            'items.*.batch_id'                  => ['nullable', 'integer'],
            'items.*.qty'                       => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'                => ['nullable', 'numeric', 'min:0'],
            'items.*.mrp'                       => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type'             => ['nullable', Rule::in([1, 2])],
            'items.*.discount_value'            => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_percent'               => ['nullable', 'numeric', 'min:0'],
            'items.*.purchase_price'            => ['nullable', 'numeric', 'min:0'],
            'items.*.is_free_item'              => ['nullable', 'boolean'],
            'payments'                          => ['nullable', 'array'],
            'payments.*.payment_mode'           => ['required_with:payments', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'payments.*.amount'                 => ['required_with:payments', 'numeric', 'min:0'],
            'payments.*.reference_no'           => ['nullable', 'string', 'max:100'],
            'payments.*.payment_date'           => ['nullable', 'date'],
            'payments.*.remarks'                => ['nullable', 'string'],
            'credit_notes'                      => ['nullable', 'array'],
            'credit_notes.*.credit_note_id'     => ['required_with:credit_notes', 'integer'],
            'credit_notes.*.adjusted_amount'    => ['required_with:credit_notes', 'numeric', 'min:0'],
        ];
    }
}
