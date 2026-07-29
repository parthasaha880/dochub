<?php

namespace App\Modules\Users\Repositories;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Modules\Users\Repositories\Contracts\UserManagementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserManagementRepository implements UserManagementRepositoryInterface
{
    public function paginateUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()->with(['roles'])->latest();

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findUser(string $id): User
    {
        return User::query()->with(['roles', 'permissions'])->findOrFail($id);
    }

    public function createUser(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function updateUser(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh()->load(['roles', 'permissions']);
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }

    public function paginateRoles(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Role::query()->with('permissions')->orderBy('hierarchy_level')->orderBy('name');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findRole(string $id): Role
    {
        return Role::query()->with('permissions')->findOrFail($id);
    }

    public function createRole(array $attributes): Role
    {
        return Role::query()->create($attributes);
    }

    public function updateRole(Role $role, array $attributes): Role
    {
        $role->update($attributes);

        return $role->refresh()->load('permissions');
    }

    public function deleteRole(Role $role): void
    {
        $role->delete();
    }

    public function paginatePermissions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Permission::query()->orderBy('group')->orderBy('name');

        if (! empty($filters['group'])) {
            $query->where('group', $filters['group']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findPermission(string $id): Permission
    {
        return Permission::query()->findOrFail($id);
    }

    public function createPermission(array $attributes): Permission
    {
        return Permission::query()->create($attributes);
    }

    public function updatePermission(Permission $permission, array $attributes): Permission
    {
        $permission->update($attributes);

        return $permission->refresh();
    }

    public function deletePermission(Permission $permission): void
    {
        $permission->delete();
    }

    public function permissionGroups(): Collection
    {
        return Permission::query()
            ->select('group')
            ->whereNotNull('group')
            ->where('group', '!=', '')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->values();
    }

    public function allRoles(): Collection
    {
        return Role::query()->orderBy('hierarchy_level')->orderBy('name')->get(['id', 'name', 'hierarchy_level', 'is_system']);
    }

    public function allPermissions(): Collection
    {
        return Permission::query()->orderBy('group')->orderBy('name')->get(['id', 'name', 'group', 'description']);
    }
}
