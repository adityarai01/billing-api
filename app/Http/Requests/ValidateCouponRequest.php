<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'cart_items'  => ['required', 'array', 'min:1'],
            'cart_total'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
