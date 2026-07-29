<?php

namespace App\Modules\Documents\Policies;

use App\Models\User;
use App\Modules\Documents\Models\Folder;

class FolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.view') || $user->can('folders.manage');
    }

    public function view(User $user, Folder $folder): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('folders.manage') || $user->can('documents.manage');
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->create($user);
    }
}
