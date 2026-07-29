<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Modules\Organization\Http\Requests\OfficeRequest;
use App\Modules\Organization\Http\Resources\OfficeResource;
use App\Modules\Organization\Models\Office;
use Illuminate\Http\JsonResponse;

class OfficeController extends AbstractOrganizationResourceController
{
    protected function modelClass(): string
    {
        return Office::class;
    }

    protected function resourceClass(): string
    {
        return OfficeResource::class;
    }

    protected function defaultWith(): array
    {
        return ['branch'];
    }

    public function store(OfficeRequest $request): JsonResponse
    {
        return $this->storeValidated($request->validated());
    }

    public function update(OfficeRequest $request, string $office): JsonResponse
    {
        return $this->updateValidated($office, $request->validated());
    }
}
