<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Modules\Organization\Http\Requests\OrganizationRequest;
use App\Modules\Organization\Http\Resources\OrganizationResource;
use App\Modules\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;

class OrganizationController extends AbstractOrganizationResourceController
{
    protected function modelClass(): string
    {
        return Organization::class;
    }

    protected function resourceClass(): string
    {
        return OrganizationResource::class;
    }

    public function store(OrganizationRequest $request): JsonResponse
    {
        return $this->storeValidated($request->validated());
    }

    public function update(OrganizationRequest $request, string $organization): JsonResponse
    {
        return $this->updateValidated($organization, $request->validated());
    }

    public function tree(string $organization): JsonResponse
    {
        $model = $this->service->show(Organization::class, $organization);
        $this->authorize('view', $model);

        return ApiResponse::success($this->service->tree($organization));
    }
}
