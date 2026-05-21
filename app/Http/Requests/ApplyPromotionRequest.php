<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_items'   => ['required', 'array', 'min:1'],
            'customer_id'  => ['nullable', 'integer', 'exists:customers,id'],
            'payment_mode' => ['nullable', 'integer'],
            'coupon_code'  => ['nullable', 'string'],
        ];
    }
}
