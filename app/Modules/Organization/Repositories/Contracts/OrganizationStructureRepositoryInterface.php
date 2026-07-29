<?php

namespace App\Modules\Organization\Repositories\Contracts;

use App\Modules\Organization\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface OrganizationStructureRepositoryInterface
{
    public function paginate(string $modelClass, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(string $modelClass, string $id, array $with = []): Model;

    public function create(string $modelClass, array $attributes): Model;

    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): void;

    public function listOptions(string $modelClass, ?string $organizationId = null): Collection;

    public function organizationTree(Organization $organization): array;
}
