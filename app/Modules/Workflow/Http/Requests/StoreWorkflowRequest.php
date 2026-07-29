<?php

namespace App\Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('workflows', 'code')->where(fn ($q) => $q->where('organization_id', $this->input('organization_id'))),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'category_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string', 'max:150'],
            'steps.*.description' => ['nullable', 'string'],
            'steps.*.step_order' => ['nullable', 'integer', 'min:1'],
            'steps.*.role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'steps.*.approver_user_ids' => ['nullable', 'array'],
            'steps.*.approver_user_ids.*' => ['uuid', 'exists:users,id'],
        ];
    }
}
