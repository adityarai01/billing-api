<?php

namespace App\Http\Request\Webmaster\Shop;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Shop (Organization) fields
            'shop_name'           => ['required', 'string', 'max:150'],
            'business_name'       => ['nullable', 'string', 'max:150'],
            'owner_name'          => ['required', 'string', 'max:150'],
            'shop_type'           => ['required', 'integer', Rule::in([
                Organization::SHOP_TYPE_MEDICAL,
                Organization::SHOP_TYPE_CLOTH,
                Organization::SHOP_TYPE_GROCERY,
                Organization::SHOP_TYPE_GENERAL,
            ])],
            'mobile_no'           => ['required', 'string', 'max:20'],
            'alternate_mobile_no' => ['nullable', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:150', 'unique:organizations,email'],
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

            // Shopkeeper (User) fields
            'shopkeeper'                    => ['required', 'array'],
            'shopkeeper.name'               => ['required', 'string', 'max:255'],
            'shopkeeper.email'              => ['required', 'email', 'max:255', 'unique:users,email'],
            'shopkeeper.mobile_no'          => ['required', 'string', 'max:20', 'unique:users,mobile_no'],
            'shopkeeper.password'           => ['required', 'confirmed', Password::min(8)],
            'shopkeeper.password_confirmation' => ['required', 'string'],
            'shopkeeper.gender'             => ['nullable', 'integer', Rule::in([
                User::GENDER_MALE,
                User::GENDER_FEMALE,
                User::GENDER_OTHER,
            ])],
            'shopkeeper.dob'                => ['nullable', 'date', 'before:today'],
            'shopkeeper.address'            => ['nullable', 'string'],
            'shopkeeper.city'               => ['nullable', 'string', 'max:100'],
            'shopkeeper.state'              => ['nullable', 'string', 'max:100'],
            'shopkeeper.pincode'            => ['nullable', 'string', 'max:10'],
        ];
    }
}
