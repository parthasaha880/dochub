<?php

namespace App\Modules\Archive\Policies;

use App\Models\User;
use App\Modules\Archive\Models\ArchiveLocation;

class ArchiveLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('archive.view') || $user->can('documents.view');
    }

    public function view(User $user, ArchiveLocation $location): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('archive.manage') || $user->can('documents.manage');
    }

    public function update(User $user, ArchiveLocation $location): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ArchiveLocation $location): bool
    {
        return $this->create($user);
    }
}
