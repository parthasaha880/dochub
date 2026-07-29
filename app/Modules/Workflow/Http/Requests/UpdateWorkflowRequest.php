<?php

namespace App\Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $workflowId = $this->route('workflow');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('workflows', 'code')->ignore($workflowId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'category_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'steps' => ['sometimes', 'array', 'min:1'],
            'steps.*.name' => ['required_with:steps', 'string', 'max:150'],
            'steps.*.description' => ['nullable', 'string'],
            'steps.*.step_order' => ['nullable', 'integer', 'min:1'],
            'steps.*.role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'steps.*.approver_user_ids' => ['nullable', 'array'],
            'steps.*.approver_user_ids.*' => ['uuid', 'exists:users,id'],
        ];
    }
}
