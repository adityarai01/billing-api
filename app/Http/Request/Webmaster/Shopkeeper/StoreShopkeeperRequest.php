<?php

namespace App\Http\Request\Webmaster\Shopkeeper;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreShopkeeperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_no'       => ['required', 'string', 'max:20', 'unique:users,mobile_no'],
            'password'        => ['required', 'confirmed', Password::min(8)],
            'gender'          => ['nullable', 'integer', Rule::in([
                User::GENDER_MALE,
                User::GENDER_FEMALE,
                User::GENDER_OTHER,
            ])],
            'dob'             => ['nullable', 'date', 'before:today'],
            'address'         => ['nullable', 'string'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'pincode'         => ['nullable', 'string', 'max:10'],
            'status'          => ['nullable', 'integer', Rule::in([
                User::STATUS_ACTIVE,
                User::STATUS_INACTIVE,
            ])],
        ];
    }
}
