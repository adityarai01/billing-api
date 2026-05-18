<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SupplierRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'code'                 => ['nullable', 'string', 'max:100'],
            'mobile_no'            => ['nullable', 'string', 'max:20'],
            'alternate_mobile_no'  => ['nullable', 'string', 'max:20'],
            'email'                => ['nullable', 'email', 'max:255'],
            'gstin'                => ['nullable', 'string', 'max:20'],
            'pan_no'               => ['nullable', 'string', 'max:20'],
            'address'              => ['nullable', 'string'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'state'                => ['nullable', 'string', 'max:100'],
            'state_code'           => ['nullable', 'string', 'max:10'],
            'pincode'              => ['nullable', 'string', 'max:10'],
            'country'              => ['nullable', 'string', 'max:100'],
            'opening_balance'      => ['nullable', 'numeric', 'min:0'],
            'balance_type'         => ['nullable', 'integer', 'in:1,2'],
            'remarks'              => ['nullable', 'string'],
            'status'               => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}
