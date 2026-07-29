<?php

namespace App\Modules\Organization\Repositories;

use App\Modules\Organization\Models\Branch;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Models\Section;
use App\Modules\Organization\Models\Unit;
use App\Modules\Organization\Repositories\Contracts\OrganizationStructureRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OrganizationStructureRepository implements OrganizationStructureRepositoryInterface
{
    public function paginate(string $modelClass, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var Builder $query */
        $query = $modelClass::query()->latest('created_at');

        if (! empty($filters['organization_id'])) {
            if ($modelClass === Organization::class) {
                $query->whereKey($filters['organization_id']);
            } else {
                $query->where('organization_id', $filters['organization_id']);
            }
        }

        foreach (['department_id', 'section_id', 'branch_id', 'unit_id', 'office_id', 'designation_id'] as $key) {
            if (! empty($filters[$key]) && $this->hasColumn($modelClass, $key)) {
                $query->where($key, $filters[$key]);
            }
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['employment_status']) && $this->hasColumn($modelClass, 'employment_status')) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search, $modelClass): void {
                if ($this->hasColumn($modelClass, 'employee_code')) {
                    $builder->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    return;
                }

                if ($this->hasColumn($modelClass, 'name')) {
                    $builder->where('name', 'like', "%{$search}%");
                }

                if ($this->hasColumn($modelClass, 'code')) {
                    $builder->orWhere('code', 'like', "%{$search}%");
                }

                if ($this->hasColumn($modelClass, 'legal_name')) {
                    $builder->orWhere('legal_name', 'like', "%{$search}%");
                }
            });
        }

        $with = $filters['with'] ?? [];
        if ($with !== []) {
            $query->with($with);
        }

        return $query->paginate($perPage);
    }

    public function findOrFail(string $modelClass, string $id, array $with = []): Model
    {
        return $modelClass::query()->with($with)->findOrFail($id);
    }

    public function create(string $modelClass, array $attributes): Model
    {
        return $modelClass::query()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->refresh();
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    public function listOptions(string $modelClass, ?string $organizationId = null): Collection
    {
        $query = $modelClass::query()->active()->orderBy('name');

        if ($organizationId && $modelClass !== Organization::class) {
            $query->where('organization_id', $organizationId);
        }

        if ($modelClass === Organization::class) {
            return $query->get(['id', 'code', 'name']);
        }

        if ($this->hasColumn($modelClass, 'employee_code')) {
            return $query->get(['id', 'employee_code', 'first_name', 'last_name', 'organization_id']);
        }

        return $query->get(['id', 'code', 'name', 'organization_id']);
    }

    public function organizationTree(Organization $organization): array
    {
        $organization->load([
            'branches' => fn ($q) => $q->active()->orderBy('name'),
            'departments' => fn ($q) => $q->active()->orderBy('name'),
            'departments.sections' => fn ($q) => $q->active()->orderBy('name'),
            'departments.sections.units' => fn ($q) => $q->active()->orderBy('name'),
            'departments.units' => fn ($q) => $q->whereNull('section_id')->active()->orderBy('name'),
            'offices' => fn ($q) => $q->active()->orderBy('name'),
        ]);

        return [
            'id' => $organization->id,
            'type' => 'organization',
            'code' => $organization->code,
            'name' => $organization->name,
            'children' => [
                [
                    'type' => 'group',
                    'name' => 'Branches',
                    'children' => $organization->branches->map(fn (Branch $branch) => [
                        'id' => $branch->id,
                        'type' => 'branch',
                        'code' => $branch->code,
                        'name' => $branch->name,
                    ])->values()->all(),
                ],
                [
                    'type' => 'group',
                    'name' => 'Departments',
                    'children' => $organization->departments->map(function (Department $department) {
                        return [
                            'id' => $department->id,
                            'type' => 'department',
                            'code' => $department->code,
                            'name' => $department->name,
                            'children' => [
                                ...$department->sections->map(fn (Section $section) => [
                                    'id' => $section->id,
                                    'type' => 'section',
                                    'code' => $section->code,
                                    'name' => $section->name,
                                    'children' => $section->units->map(fn (Unit $unit) => [
                                        'id' => $unit->id,
                                        'type' => 'unit',
                                        'code' => $unit->code,
                                        'name' => $unit->name,
                                    ])->values()->all(),
                                ])->values()->all(),
                                ...$department->units->map(fn (Unit $unit) => [
                                    'id' => $unit->id,
                                    'type' => 'unit',
                                    'code' => $unit->code,
                                    'name' => $unit->name,
                                ])->values()->all(),
                            ],
                        ];
                    })->values()->all(),
                ],
                [
                    'type' => 'group',
                    'name' => 'Offices',
                    'children' => $organization->offices->map(fn ($office) => [
                        'id' => $office->id,
                        'type' => 'office',
                        'code' => $office->code,
                        'name' => $office->name,
                    ])->values()->all(),
                ],
            ],
        ];
    }

    private function hasColumn(string $modelClass, string $column): bool
    {
        /** @var Model $model */
        $model = new $modelClass;

        return in_array($column, $model->getFillable(), true);
    }
}
