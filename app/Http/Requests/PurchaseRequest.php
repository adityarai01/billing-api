<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PurchaseRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules(): array
    {
        $hasItems = $this->has('items');
        return [
            'supplier_id'               => ['nullable', 'integer'],
            'purchase_date'             => [$hasItems ? 'required' : 'nullable', 'date'],
            'due_date'                  => ['nullable', 'date'],
            'supplier_invoice_no'       => ['nullable', 'string', 'max:100'],
            'discount_type'             => ['nullable', 'integer', 'in:1,2'],
            'discount_value'            => ['nullable', 'numeric', 'min:0'],
            'other_charges'             => ['nullable', 'numeric', 'min:0'],
            'round_off'                 => ['nullable', 'numeric'],
            'remarks'                   => ['nullable', 'string'],
            'purchase_status'           => ['nullable', 'integer', 'in:1,2'],
            'items'                     => [$hasItems ? 'required' : 'nullable', 'array', 'min:1'],
            'items.*.product_id'        => ['required_with:items', 'integer'],
            'items.*.product_variant_id'=> ['required_with:items', 'integer'],
            'items.*.batch_id'          => ['nullable', 'integer'],
            'items.*.qty'               => ['required_with:items', 'numeric', 'min:0'],
            'items.*.free_qty'          => ['nullable', 'numeric', 'min:0'],
            'items.*.purchase_price'    => ['required_with:items', 'numeric', 'min:0'],
            'items.*.mrp'               => ['nullable', 'numeric', 'min:0'],
            'items.*.selling_price'     => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_percent'       => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type'     => ['nullable', 'integer', 'in:1,2'],
            'items.*.discount_value'    => ['nullable', 'numeric', 'min:0'],
            'items.*.mfg_date'          => ['nullable', 'date'],
            'items.*.expiry_date'       => ['nullable', 'date'],
            'payments'                  => ['nullable', 'array'],
            'payments.*.payment_mode'   => ['required_with:payments', 'integer', 'in:1,2,3,4,5,6'],
            'payments.*.amount'         => ['required_with:payments', 'numeric', 'min:0'],
            'payments.*.payment_date'   => ['nullable', 'date'],
            'payments.*.reference_no'   => ['nullable', 'string'],
        ];
    }
}
