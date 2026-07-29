<?php

namespace App\Modules\Workflow\Policies;

use App\Models\User;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowInstance;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('workflow.view') || $user->can('workflow.manage');
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('workflow.manage');
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->can('workflow.manage');
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->can('workflow.manage');
    }

    public function approve(User $user, WorkflowInstance $instance): bool
    {
        return $user->can('workflow.approve') || $user->can('workflow.manage');
    }

    public function submit(User $user): bool
    {
        return $user->can('workflow.submit') || $user->can('workflow.manage') || $user->can('documents.upload');
    }

    public function viewInbox(User $user): bool
    {
        return $user->can('workflow.approve') || $user->can('workflow.view') || $user->can('workflow.manage');
    }
}
