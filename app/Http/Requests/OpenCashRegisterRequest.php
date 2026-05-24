<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'opening_cash' => 'required|numeric|min:0',
            'opening_note' => 'nullable|string|max:500',
        ];
    }
}
