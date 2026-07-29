<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->ignore($roleId)->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'hierarchy_level' => ['nullable', 'integer', 'min:1', 'max:999'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => strtolower(str_replace(' ', '_', trim((string) $this->input('name'))))]);
        }
    }
}
