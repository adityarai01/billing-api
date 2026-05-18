<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $orgId = $this->attributes->get('organization_id');
        $id    = $this->input('id');

        return [
            'name'            => ['nullable', 'string', 'max:200'],
            'mobile_no'       => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:200'],
            'gstin'           => ['nullable', 'string', 'max:20'],
            'pan_no'          => ['nullable', 'string', 'max:15'],
            'address'         => ['nullable', 'string'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'state_code'      => ['nullable', 'string', 'max:10'],
            'pincode'         => ['nullable', 'string', 'max:10'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'loyalty_points'  => ['nullable', 'numeric', 'min:0'],
            'status'          => ['nullable', Rule::in([0, 1])],
        ];
    }
}
