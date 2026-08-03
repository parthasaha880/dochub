<?php

namespace App\Modules\Archive\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArchiveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'qr_code' => ['nullable', 'string', 'max:64'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
