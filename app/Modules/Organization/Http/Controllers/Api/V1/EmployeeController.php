<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Modules\Organization\Http\Requests\EmployeeRequest;
use App\Modules\Organization\Http\Resources\EmployeeResource;
use App\Modules\Organization\Models\Employee;
use Illuminate\Http\JsonResponse;

class EmployeeController extends AbstractOrganizationResourceController
{
    protected function modelClass(): string
    {
        return Employee::class;
    }

    protected function resourceClass(): string
    {
        return EmployeeResource::class;
    }

    protected function defaultWith(): array
    {
        return ['department', 'designation', 'branch'];
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        return $this->storeValidated($request->validated());
    }

    public function update(EmployeeRequest $request, string $employee): JsonResponse
    {
        return $this->updateValidated($employee, $request->validated());
    }
}
