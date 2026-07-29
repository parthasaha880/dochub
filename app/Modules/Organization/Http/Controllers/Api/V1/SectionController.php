<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Modules\Organization\Http\Requests\SectionRequest;
use App\Modules\Organization\Http\Resources\SectionResource;
use App\Modules\Organization\Models\Section;
use Illuminate\Http\JsonResponse;

class SectionController extends AbstractOrganizationResourceController
{
    protected function modelClass(): string
    {
        return Section::class;
    }

    protected function resourceClass(): string
    {
        return SectionResource::class;
    }

    protected function defaultWith(): array
    {
        return ['department'];
    }

    public function store(SectionRequest $request): JsonResponse
    {
        return $this->storeValidated($request->validated());
    }

    public function update(SectionRequest $request, string $section): JsonResponse
    {
        return $this->updateValidated($section, $request->validated());
    }
}
