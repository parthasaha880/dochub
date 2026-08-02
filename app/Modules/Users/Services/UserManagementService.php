<?php

namespace App\Modules\Users\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Modules\Authentication\Notifications\WelcomeUserNotification;
use App\Modules\Users\Repositories\Contracts\UserManagementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository
    ) {}

    public function paginateUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateUsers($filters, $perPage);
    }

    public function showUser(string $id): User
    {
        return $this->repository->findUser($id);
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roles = $data['roles'] ?? [];
            $directPermissions = $data['permissions'] ?? [];
            $temporaryPassword = $data['password'] ?? null;
            unset($data['roles'], $data['permissions']);

            if (! empty($data['password'])) {
                $data['password_changed_at'] = now();
            }

            $user = $this->repository->createUser($data);

            if ($roles !== []) {
                $user->syncRoles($roles);
            }

            if ($directPermissions !== []) {
                $user->syncPermissions($directPermissions);
            }

            $user = $user->load(['roles', 'permissions']);

            $user->notify(new WelcomeUserNotification(
                temporaryPassword: is_string($temporaryPassword) ? $temporaryPassword : null,
            ));

            return $user;
        });
    }

    public function updateUser(string $id, array $data, User $actor): User
    {
        return DB::transaction(function () use ($id, $data, $actor) {
            $user = $this->repository->findUser($id);

            if ($actor->id === $user->id && array_key_exists('is_active', $data) && ! $data['is_active']) {
                throw ValidationException::withMessages([
                    'is_active' => ['You cannot deactivate your own account.'],
                ]);
            }

            $roles = $data['roles'] ?? null;
            $directPermissions = $data['permissions'] ?? null;
            unset($data['roles'], $data['permissions']);

            if (array_key_exists('password', $data)) {
                if (blank($data['password'])) {
                    unset($data['password']);
                } else {
                    $data['password_changed_at'] = now();
                }
            }

            $user = $this->repository->updateUser($user, $data);

            if (is_array($roles)) {
                $this->guardRoleDemotion($actor, $user, $roles);
                $user->syncRoles($roles);
            }

            if (is_array($directPermissions)) {
                $user->syncPermissions($directPermissions);
            }

            return $user->load(['roles', 'permissions']);
        });
    }

    public function deleteUser(string $id, User $actor): void
    {
        $user = $this->repository->findUser($id);

        if ($actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => ['Cannot delete the last super admin.'],
            ]);
        }

        $this->repository->deleteUser($user);
    }

    public function paginateRoles(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateRoles($filters, $perPage);
    }

    public function showRole(string $id): Role
    {
        return $this->repository->findRole($id);
    }

    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $permissions = $data['permissions'] ?? [];
            unset($data['permissions']);

            $data['guard_name'] = $data['guard_name'] ?? 'web';
            $data['is_system'] = false;

            $role = $this->repository->createRole($data);

            if ($permissions !== []) {
                $role->syncPermissions($permissions);
            }

            return $role->load('permissions');
        });
    }

    public function updateRole(string $id, array $data): Role
    {
        return DB::transaction(function () use ($id, $data) {
            $role = $this->repository->findRole($id);
            $permissions = $data['permissions'] ?? null;
            unset($data['permissions']);

            if ($role->is_system && isset($data['name']) && $data['name'] !== $role->name) {
                throw ValidationException::withMessages([
                    'name' => ['System role names cannot be renamed.'],
                ]);
            }

            unset($data['is_system']);

            $role = $this->repository->updateRole($role, $data);

            if (is_array($permissions)) {
                $role->syncPermissions($permissions);
            }

            return $role->load('permissions');
        });
    }

    public function deleteRole(string $id): void
    {
        $role = $this->repository->findRole($id);

        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => ['System roles cannot be deleted.'],
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['Cannot delete a role that is assigned to users.'],
            ]);
        }

        $this->repository->deleteRole($role);
    }

    public function paginatePermissions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginatePermissions($filters, $perPage);
    }

    public function showPermission(string $id): Permission
    {
        return $this->repository->findPermission($id);
    }

    public function createPermission(array $data): Permission
    {
        $data['guard_name'] = $data['guard_name'] ?? 'web';

        return $this->repository->createPermission($data);
    }

    public function updatePermission(string $id, array $data): Permission
    {
        $permission = $this->repository->findPermission($id);

        return $this->repository->updatePermission($permission, $data);
    }

    public function deletePermission(string $id): void
    {
        $permission = $this->repository->findPermission($id);

        if ($permission->roles()->exists()) {
            throw ValidationException::withMessages([
                'permission' => ['Cannot delete a permission assigned to roles.'],
            ]);
        }

        $this->repository->deletePermission($permission);
    }

    public function permissionGroups(): Collection
    {
        return $this->repository->permissionGroups();
    }

    public function roleOptions(): Collection
    {
        return $this->repository->allRoles();
    }

    public function permissionOptions(): Collection
    {
        return $this->repository->allPermissions();
    }

    public function permissionsByGroup(): Collection
    {
        return $this->repository->allPermissions()->groupBy(fn (Permission $p) => $p->group ?: 'general');
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function guardRoleDemotion(User $actor, User $user, array $roles): void
    {
        if ($actor->id !== $user->id) {
            return;
        }

        if ($user->hasRole('super_admin') && ! in_array('super_admin', $roles, true)) {
            throw ValidationException::withMessages([
                'roles' => ['You cannot remove the super admin role from yourself.'],
            ]);
        }
    }
}
