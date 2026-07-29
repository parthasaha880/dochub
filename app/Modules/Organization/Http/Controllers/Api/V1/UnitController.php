<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Modules\Organization\Http\Requests\UnitRequest;
use App\Modules\Organization\Http\Resources\UnitResource;
use App\Modules\Organization\Models\Unit;
use Illuminate\Http\JsonResponse;

class UnitController extends AbstractOrganizationResourceController
{
    protected function modelClass(): string
    {
        return Unit::class;
    }

    protected function resourceClass(): string
    {
        return UnitResource::class;
    }

    protected function defaultWith(): array
    {
        return ['department', 'section'];
    }

    public function store(UnitRequest $request): JsonResponse
    {
        return $this->storeValidated($request->validated());
    }

    public function update(UnitRequest $request, string $unit): JsonResponse
    {
        return $this->updateValidated($unit, $request->validated());
    }
}
