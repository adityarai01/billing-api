<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerLedgerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'      => ['required', 'integer'],
            'transaction_date' => ['required', 'date'],
            'transaction_type' => ['required', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'debit_amount'     => ['nullable', 'numeric', 'min:0'],
            'credit_amount'    => ['nullable', 'numeric', 'min:0'],
            'reference_no'     => ['nullable', 'string', 'max:100'],
            'remarks'          => ['nullable', 'string'],
        ];
    }
}
