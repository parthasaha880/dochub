<?php

namespace App\Modules\Users\Repositories\Contracts;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserManagementRepositoryInterface
{
    public function paginateUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findUser(string $id): User;

    public function createUser(array $attributes): User;

    public function updateUser(User $user, array $attributes): User;

    public function deleteUser(User $user): void;

    public function paginateRoles(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findRole(string $id): Role;

    public function createRole(array $attributes): Role;

    public function updateRole(Role $role, array $attributes): Role;

    public function deleteRole(Role $role): void;

    public function paginatePermissions(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findPermission(string $id): Permission;

    public function createPermission(array $attributes): Permission;

    public function updatePermission(Permission $permission, array $attributes): Permission;

    public function deletePermission(Permission $permission): void;

    public function permissionGroups(): Collection;

    public function allRoles(): Collection;

    public function allPermissions(): Collection;
}
