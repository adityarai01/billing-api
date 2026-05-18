<?php

namespace App\Http\Request\Webmaster\Shop;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shopId = $this->route('shop');

        // Resolve the shopkeeper's user ID for unique-ignore rules
        $shopkeeperId = Organization::where('id', $shopId)
            ->whereHas('users', fn ($q) => $q->where('user_type', User::TYPE_SHOP_OWNER))
            ->with(['users' => fn ($q) => $q->where('user_type', User::TYPE_SHOP_OWNER)->select('id', 'organization_id')])
            ->value('id');

        $userId = null;
        if ($shopkeeperId) {
            $userId = User::where('organization_id', $shopId)
                ->where('user_type', User::TYPE_SHOP_OWNER)
                ->value('id');
        }

        return [
            // Shop (Organization) fields
            'shop_name'           => ['sometimes', 'string', 'max:150'],
            'business_name'       => ['nullable', 'string', 'max:150'],
            'owner_name'          => ['sometimes', 'string', 'max:150'],
            'shop_type'           => ['sometimes', 'integer', Rule::in([
                Organization::SHOP_TYPE_MEDICAL,
                Organization::SHOP_TYPE_CLOTH,
                Organization::SHOP_TYPE_GROCERY,
                Organization::SHOP_TYPE_GENERAL,
            ])],
            'mobile_no'           => ['sometimes', 'string', 'max:20'],
            'alternate_mobile_no' => ['nullable', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:150', Rule::unique('organizations', 'email')->ignore($shopId)],
            'gstin'               => ['nullable', 'string', 'max:20'],
            'pan_no'              => ['nullable', 'string', 'max:20'],
            'address'             => ['nullable', 'string'],
            'city'                => ['nullable', 'string', 'max:100'],
            'state'               => ['nullable', 'string', 'max:100'],
            'state_code'          => ['nullable', 'string', 'max:10'],
            'pincode'             => ['nullable', 'string', 'max:10'],
            'country'             => ['nullable', 'string', 'max:100'],
            'invoice_prefix'      => ['nullable', 'string', 'max:20'],
            'invoice_start_no'    => ['nullable', 'integer', 'min:1'],
            'currency'            => ['nullable', 'string', 'max:10'],
            'timezone'            => ['nullable', 'string', 'max:100'],
            'subscription_status' => ['nullable', 'integer', Rule::in([
                Organization::SUBSCRIPTION_TRIAL,
                Organization::SUBSCRIPTION_ACTIVE,
                Organization::SUBSCRIPTION_EXPIRED,
                Organization::SUBSCRIPTION_SUSPENDED,
            ])],
            'trial_start_date'    => ['nullable', 'date'],
            'trial_end_date'      => ['nullable', 'date', 'after_or_equal:trial_start_date'],
            'status'              => ['nullable', 'integer', Rule::in([
                Organization::STATUS_ACTIVE,
                Organization::STATUS_INACTIVE,
            ])],

            // Shopkeeper (User) fields — all optional on update
            'shopkeeper'           => ['sometimes', 'array'],
            'shopkeeper.name'      => ['sometimes', 'string', 'max:255'],
            'shopkeeper.email'     => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'shopkeeper.mobile_no' => ['sometimes', 'string', 'max:20', Rule::unique('users', 'mobile_no')->ignore($userId)],
            'shopkeeper.password'  => ['nullable', 'confirmed', Password::min(8)],
            'shopkeeper.password_confirmation' => ['nullable', 'string'],
            'shopkeeper.gender'    => ['nullable', 'integer', Rule::in([
                User::GENDER_MALE,
                User::GENDER_FEMALE,
                User::GENDER_OTHER,
            ])],
            'shopkeeper.dob'       => ['nullable', 'date', 'before:today'],
            'shopkeeper.address'   => ['nullable', 'string'],
            'shopkeeper.city'      => ['nullable', 'string', 'max:100'],
            'shopkeeper.state'     => ['nullable', 'string', 'max:100'],
            'shopkeeper.pincode'   => ['nullable', 'string', 'max:10'],
        ];
    }
}
