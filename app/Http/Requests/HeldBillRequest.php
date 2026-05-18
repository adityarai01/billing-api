<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HeldBillRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'                => ['nullable', 'integer'],
            'remarks'                    => ['nullable', 'string'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_id'         => ['required', 'integer'],
            'items.*.product_variant_id' => ['required', 'integer'],
            'items.*.batch_id'           => ['nullable', 'integer'],
            'items.*.product_name'       => ['nullable', 'string'],
            'items.*.variant_name'       => ['nullable', 'string'],
            'items.*.batch_no'           => ['nullable', 'string'],
            'items.*.qty'                => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'         => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
