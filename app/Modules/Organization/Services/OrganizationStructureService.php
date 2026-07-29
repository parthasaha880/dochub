<?php

namespace App\Modules\Organization\Services;

use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Repositories\Contracts\OrganizationStructureRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationStructureService
{
    public function __construct(
        private readonly OrganizationStructureRepositoryInterface $repository
    ) {}

    public function paginate(string $modelClass, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($modelClass, $filters, $perPage);
    }

    public function show(string $modelClass, string $id, array $with = []): Model
    {
        return $this->repository->findOrFail($modelClass, $id, $with);
    }

    public function create(string $modelClass, array $attributes): Model
    {
        return DB::transaction(fn () => $this->repository->create($modelClass, $attributes));
    }

    public function update(string $modelClass, string $id, array $attributes): Model
    {
        return DB::transaction(function () use ($modelClass, $id, $attributes) {
            $model = $this->repository->findOrFail($modelClass, $id);

            return $this->repository->update($model, $attributes);
        });
    }

    public function delete(string $modelClass, string $id): void
    {
        DB::transaction(function () use ($modelClass, $id): void {
            $model = $this->repository->findOrFail($modelClass, $id);

            if ($model instanceof Organization) {
                if ($model->employees()->exists()) {
                    throw ValidationException::withMessages([
                        'organization' => ['Cannot delete an organization that still has employees.'],
                    ]);
                }
            }

            $this->repository->delete($model);
        });
    }

    public function options(string $modelClass, ?string $organizationId = null): Collection
    {
        return $this->repository->listOptions($modelClass, $organizationId);
    }

    public function tree(string $organizationId): array
    {
        /** @var Organization $organization */
        $organization = $this->repository->findOrFail(Organization::class, $organizationId);

        return $this->repository->organizationTree($organization);
    }
}
