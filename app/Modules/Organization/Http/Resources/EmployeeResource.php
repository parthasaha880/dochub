<?php

namespace App\Modules\Organization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Organization\Models\Employee */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'employee_code' => $this->employee_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'department_id' => $this->department_id,
            'section_id' => $this->section_id,
            'unit_id' => $this->unit_id,
            'branch_id' => $this->branch_id,
            'office_id' => $this->office_id,
            'designation_id' => $this->designation_id,
            'reporting_to' => $this->reporting_to,
            'joining_date' => $this->joining_date?->toDateString(),
            'leaving_date' => $this->leaving_date?->toDateString(),
            'employment_status' => $this->employment_status?->value ?? $this->employment_status,
            'is_active' => $this->is_active,
            'remarks' => $this->remarks,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'designation' => $this->whenLoaded('designation', fn () => [
                'id' => $this->designation?->id,
                'name' => $this->designation?->name,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
