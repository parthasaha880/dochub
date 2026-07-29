<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Modules\Organization\Http\Requests\DesignationRequest;
use App\Modules\Organization\Http\Resources\DesignationResource;
use App\Modules\Organization\Models\Designation;
use Illuminate\Http\JsonResponse;

class DesignationController extends AbstractOrganizationResourceController
{
    protected function modelClass(): string
    {
        return Designation::class;
    }

    protected function resourceClass(): string
    {
        return DesignationResource::class;
    }

    public function store(DesignationRequest $request): JsonResponse
    {
        return $this->storeValidated($request->validated());
    }

    public function update(DesignationRequest $request, string $designation): JsonResponse
    {
        return $this->updateValidated($designation, $request->validated());
    }
}
