<?php
namespace App\Http\Requests;

use App\Enums\DiscountLevelEnum;
use App\Enums\PromotionApplyTypeEnum;
use App\Enums\PromotionDiscountTypeEnum;
use App\Enums\PromotionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:200'],
            'code'                => ['nullable', 'string', 'max:100'],
            'description'         => ['nullable', 'string'],
            'promotion_type'      => ['required', 'integer', Rule::in(PromotionTypeEnum::values())],
            'discount_level'      => ['required', 'integer', Rule::in(DiscountLevelEnum::values())],
            'apply_type'          => ['required', 'integer', Rule::in(PromotionApplyTypeEnum::values())],
            'discount_type'       => ['nullable', 'integer', Rule::in(PromotionDiscountTypeEnum::values())],
            'discount_value'      => ['nullable', 'numeric', 'min:0'],
            'min_bill_amount'     => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date'          => ['nullable', 'date'],
            'end_date'            => ['nullable', 'date', 'after_or_equal:start_date'],
            'usage_limit'         => ['nullable', 'integer', 'min:1'],
            'per_customer_limit'  => ['nullable', 'integer', 'min:1'],
            'priority'            => ['nullable', 'integer'],
            'allow_multiple'      => ['nullable', 'integer', Rule::in([0, 1])],
            'stackable'           => ['nullable', 'integer', Rule::in([0, 1])],
            'auto_apply'          => ['nullable', 'integer', Rule::in([0, 1])],
            'requires_coupon'     => ['nullable', 'integer', Rule::in([0, 1])],
            'status'              => ['nullable', 'integer', Rule::in([0, 1])],
            'targets'             => ['nullable', 'array'],
            'targets.*.target_type' => ['required_with:targets', 'integer'],
            'targets.*.target_id'   => ['nullable', 'integer'],
            'conditions'          => ['nullable', 'array'],
            'buy_get_rules'       => ['nullable', 'array'],
            'combo_items'         => ['nullable', 'array'],
            'free_items'          => ['nullable', 'array'],
        ];
    }
}
