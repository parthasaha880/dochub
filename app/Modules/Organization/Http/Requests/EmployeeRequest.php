<?php

namespace App\Modules\Organization\Http\Requests;

use App\Modules\Organization\Enums\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('employee');
        $orgId = $this->input('organization_id');

        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($id)->whereNull('deleted_at')],
            'employee_code' => [
                'required', 'string', 'max:50',
                Rule::unique('employees', 'employee_code')->ignore($id)->where(fn ($q) => $q->where('organization_id', $orgId)->whereNull('deleted_at')),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'section_id' => ['nullable', 'uuid', 'exists:sections,id'],
            'unit_id' => ['nullable', 'uuid', 'exists:units,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'office_id' => ['nullable', 'uuid', 'exists:offices,id'],
            'designation_id' => ['nullable', 'uuid', 'exists:designations,id'],
            'reporting_to' => ['nullable', 'uuid', 'exists:employees,id'],
            'joining_date' => ['nullable', 'date'],
            'leaving_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('employee_code')) {
            $this->merge(['employee_code' => strtoupper(trim((string) $this->input('employee_code')))]);
        }
    }
}
