<?php

namespace App\Modules\Organization\Policies;

use App\Models\User;
use App\Modules\Organization\Models\Employee;
use Illuminate\Database\Eloquent\Model;

class OrganizationStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.view') || $user->can('employees.view');
    }

    public function view(User $user, Model $model): bool
    {
        if ($model instanceof Employee) {
            return $user->can('employees.view') || $user->can('organization.view');
        }

        return $user->can('organization.view');
    }

    public function create(User $user): bool
    {
        if (request()->is('api/v1/employees', 'api/v1/employees/*')) {
            return $user->can('employees.manage') || $user->can('organization.manage');
        }

        return $user->can('organization.manage');
    }

    public function update(User $user, Model $model): bool
    {
        if ($model instanceof Employee) {
            return $user->can('employees.manage') || $user->can('organization.manage');
        }

        return $user->can('organization.manage');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }
}
