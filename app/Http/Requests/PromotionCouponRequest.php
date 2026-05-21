<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromotionCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'promotion_id'        => ['required', 'integer', 'exists:promotions,id'],
            'coupon_code'         => ['required', 'string', 'max:100'],
            'usage_limit'         => ['nullable', 'integer', 'min:1'],
            'per_customer_limit'  => ['nullable', 'integer', 'min:1'],
            'start_date'          => ['nullable', 'date'],
            'end_date'            => ['nullable', 'date', 'after_or_equal:start_date'],
            'min_bill_amount'     => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'status'              => ['nullable', 'integer'],
        ];
    }
}
