<?php

namespace App\Modules\Archive\Http\Requests;

use App\Modules\Archive\Enums\LocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArchiveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required_without:parent_id', 'nullable', 'uuid', 'exists:organizations,id'],
            'parent_id' => ['nullable', 'uuid', 'exists:archive_locations,id'],
            'type' => ['required', Rule::in(LocationType::values())],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'qr_code' => ['nullable', 'string', 'max:64'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
