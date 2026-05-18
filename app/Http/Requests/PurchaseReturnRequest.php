<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PurchaseReturnRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules(): array
    {
        return [
            'purchase_id'                   => ['nullable', 'integer'],
            'supplier_id'                   => ['nullable', 'integer'],
            'return_date'                   => ['required', 'date'],
            'settlement_type'               => ['required', 'integer', 'in:1,2,3'],
            'reason'                        => ['nullable', 'string'],
            'remarks'                       => ['nullable', 'string'],
            'items'                         => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id'      => ['required', 'integer'],
            'items.*.return_qty'            => ['required', 'numeric', 'min:0.001'],
            'items.*.total_amount'          => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
