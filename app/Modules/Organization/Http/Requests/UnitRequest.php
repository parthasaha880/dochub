<?php

namespace App\Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('unit');
        $departmentId = $this->input('department_id');

        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'section_id' => ['nullable', 'uuid', 'exists:sections,id'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('units', 'code')->ignore($id)->where(fn ($q) => $q->where('department_id', $departmentId)->whereNull('deleted_at')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }
}
