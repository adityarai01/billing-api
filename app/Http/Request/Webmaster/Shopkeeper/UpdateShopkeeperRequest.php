<?php

namespace App\Http\Request\Webmaster\Shopkeeper;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateShopkeeperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('shopkeeper');

        return [
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'name'            => ['sometimes', 'string', 'max:255'],
            'email'           => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'mobile_no'       => ['sometimes', 'string', 'max:20', Rule::unique('users', 'mobile_no')->ignore($id)],
            'password'        => ['nullable', 'confirmed', Password::min(8)],
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
