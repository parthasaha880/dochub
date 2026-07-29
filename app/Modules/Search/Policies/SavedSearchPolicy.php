<?php

namespace App\Modules\Search\Policies;

use App\Models\User;
use App\Modules\Search\Models\SavedSearch;

class SavedSearchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('search.view');
    }

    public function view(User $user, SavedSearch $savedSearch): bool
    {
        return $user->can('search.view')
            && ($savedSearch->user_id === $user->id || $savedSearch->is_shared);
    }

    public function create(User $user): bool
    {
        return $user->can('search.view');
    }

    public function update(User $user, SavedSearch $savedSearch): bool
    {
        return $savedSearch->user_id === $user->id || $user->can('search.saved.manage');
    }

    public function delete(User $user, SavedSearch $savedSearch): bool
    {
        return $this->update($user, $savedSearch);
    }

    public function search(User $user): bool
    {
        return $user->can('search.view') && $user->can('documents.view');
    }
}
