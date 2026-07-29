<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Modules\Organization\Http\Requests\DepartmentRequest;
use App\Modules\Organization\Http\Resources\DepartmentResource;
use App\Modules\Organization\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends AbstractOrganizationResourceController
{
    protected function modelClass(): string
    {
        return Department::class;
    }

    protected function resourceClass(): string
    {
        return DepartmentResource::class;
    }

    protected function defaultWith(): array
    {
        return ['organization', 'branch'];
    }

    public function store(DepartmentRequest $request): JsonResponse
    {
        return $this->storeValidated($request->validated());
    }

    public function update(DepartmentRequest $request, string $department): JsonResponse
    {
        return $this->updateValidated($department, $request->validated());
    }
}
