<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission');

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('permissions', 'name')->ignore($permissionId)->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
            'group' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => strtolower(trim((string) $this->input('name')))]);
        }
    }
}
