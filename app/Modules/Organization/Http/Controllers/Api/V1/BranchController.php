<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Modules\Organization\Http\Requests\BranchRequest;
use App\Modules\Organization\Http\Resources\BranchResource;
use App\Modules\Organization\Models\Branch;
use Illuminate\Http\JsonResponse;

class BranchController extends AbstractOrganizationResourceController
{
    protected function modelClass(): string
    {
        return Branch::class;
    }

    protected function resourceClass(): string
    {
        return BranchResource::class;
    }

    protected function defaultWith(): array
    {
        return ['organization'];
    }

    public function store(BranchRequest $request): JsonResponse
    {
        return $this->storeValidated($request->validated());
    }

    public function update(BranchRequest $request, string $branch): JsonResponse
    {
        return $this->updateValidated($branch, $request->validated());
    }
}
