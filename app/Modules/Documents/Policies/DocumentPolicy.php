<?php

namespace App\Modules\Documents\Policies;

use App\Models\User;
use App\Modules\Documents\Models\Document;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('documents.upload') || $user->can('documents.manage');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can('documents.manage') || $user->can('documents.upload');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('documents.manage') || $user->can('documents.delete');
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->can('documents.manage');
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->can('documents.manage');
    }

    public function download(User $user, Document $document): bool
    {
        return $user->can('documents.view') || $user->can('documents.download');
    }

    public function preview(User $user, Document $document): bool
    {
        return $this->download($user, $document);
    }

    public function checkOut(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function checkIn(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}
