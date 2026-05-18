<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sale_id'      => ['required', 'integer'],
            'payment_mode' => ['required', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['nullable', 'date'],
            'remarks'      => ['nullable', 'string'],
        ];
    }
}
