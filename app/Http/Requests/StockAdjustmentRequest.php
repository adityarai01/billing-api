<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StockAdjustmentRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules(): array
    {
        return [
            'adjustment_date'               => ['required', 'date'],
            'adjustment_type'               => ['required', 'integer', 'in:1,2'],
            'reason_type'                   => ['required', 'integer', 'in:1,2,3,4,5,6'],
            'remarks'                       => ['nullable', 'string'],
            'items'                         => ['required', 'array', 'min:1'],
            'items.*.product_id'            => ['nullable', 'integer'],
            'items.*.product_variant_id'    => ['required', 'integer'],
            'items.*.batch_id'              => ['nullable', 'integer'],
            'items.*.adjustment_qty'        => ['required', 'numeric', 'min:0.001'],
            'items.*.rate'                  => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks'               => ['nullable', 'string'],
        ];
    }
}
