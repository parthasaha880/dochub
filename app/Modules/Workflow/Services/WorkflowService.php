<?php

namespace App\Modules\Workflow\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Notifications\Notifications\WorkflowActivityNotification;
use App\Modules\Workflow\Enums\WorkflowActionType;
use App\Modules\Workflow\Enums\WorkflowInstanceStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowInstance;
use App\Modules\Workflow\Models\WorkflowStep;
use App\Modules\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class WorkflowService
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $repository,
        private readonly AuditLogger $audit,
    ) {}

    public function paginateWorkflows(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateWorkflows($filters, $perPage);
    }

    public function showWorkflow(string $id): Workflow
    {
        return $this->repository->findWorkflow($id);
    }

    public function createWorkflow(array $data, User $actor): Workflow
    {
        return DB::transaction(function () use ($data, $actor) {
            $steps = $data['steps'] ?? [];
            unset($data['steps']);

            $this->assertValidSteps($steps);

            if (! empty($data['is_default'])) {
                $this->repository->clearDefaultForOrganization($data['organization_id']);
            }

            $workflow = $this->repository->createWorkflow([
                ...$data,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'created_by' => $actor->id,
            ]);

            return $this->repository->syncSteps($workflow, $steps);
        });
    }

    public function updateWorkflow(string $id, array $data, User $actor): Workflow
    {
        return DB::transaction(function () use ($id, $data, $actor) {
            $workflow = $this->repository->findWorkflow($id);
            $steps = $data['steps'] ?? null;
            unset($data['steps']);

            if ($steps !== null) {
                $this->assertValidSteps($steps);
            }

            if (! empty($data['is_default'])) {
                $this->repository->clearDefaultForOrganization($workflow->organization_id, $workflow->id);
            }

            $workflow = $this->repository->updateWorkflow($workflow, [
                ...$data,
                'updated_by' => $actor->id,
            ]);

            if ($steps !== null) {
                $workflow = $this->repository->syncSteps($workflow, $steps);
            }

            return $workflow;
        });
    }

    public function deleteWorkflow(string $id): void
    {
        $workflow = $this->repository->findWorkflow($id);

        $hasOpen = WorkflowInstance::query()
            ->where('workflow_id', $workflow->id)
            ->whereIn('status', [
                WorkflowInstanceStatus::InProgress->value,
                WorkflowInstanceStatus::Returned->value,
            ])
            ->exists();

        if ($hasOpen) {
            throw ValidationException::withMessages([
                'workflow' => ['Cannot delete a workflow with open instances.'],
            ]);
        }

        $this->repository->deleteWorkflow($workflow);
    }

    public function submitDocument(string $documentId, User $actor, ?string $workflowId = null, ?string $note = null): WorkflowInstance
    {
        return DB::transaction(function () use ($documentId, $actor, $workflowId, $note) {
            $document = Document::query()->findOrFail($documentId);

            if ($this->repository->findActiveInstanceForDocument($document->id)) {
                throw ValidationException::withMessages([
                    'document' => ['Document already has an active workflow instance.'],
                ]);
            }

            if (! in_array($document->approval_status, [
                ApprovalStatus::Draft,
                ApprovalStatus::Returned,
                ApprovalStatus::Rejected,
            ], true)) {
                throw ValidationException::withMessages([
                    'document' => ['Only draft, returned, or rejected documents can be submitted.'],
                ]);
            }

            $workflow = $workflowId
                ? $this->repository->findWorkflow($workflowId)
                : $this->repository->findDefaultWorkflow($document->organization_id, $document->category_id);

            if (! $workflow || ! $workflow->is_active) {
                throw ValidationException::withMessages([
                    'workflow' => ['No active workflow available for submission.'],
                ]);
            }

            if ($workflow->organization_id !== $document->organization_id) {
                throw ValidationException::withMessages([
                    'workflow' => ['Workflow does not belong to this document organization.'],
                ]);
            }

            $firstStep = $workflow->steps->sortBy('step_order')->first();
            if (! $firstStep) {
                throw ValidationException::withMessages([
                    'workflow' => ['Workflow has no approval steps.'],
                ]);
            }

            $isResubmit = in_array($document->approval_status, [
                ApprovalStatus::Returned,
                ApprovalStatus::Rejected,
            ], true);

            $instance = $this->repository->createInstance([
                'organization_id' => $document->organization_id,
                'document_id' => $document->id,
                'workflow_id' => $workflow->id,
                'current_step_id' => $firstStep->id,
                'status' => WorkflowInstanceStatus::InProgress->value,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'submission_note' => $note,
                'created_by' => $actor->id,
            ]);

            $this->repository->createAction([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $firstStep->id,
                'action' => $isResubmit
                    ? WorkflowActionType::Resubmitted->value
                    : WorkflowActionType::Submitted->value,
                'actor_id' => $actor->id,
                'comments' => $note,
                'acted_at' => now(),
            ]);

            $document->update([
                'approval_status' => ApprovalStatus::UnderReview->value,
            ]);

            $instance = $this->repository->findInstance($instance->id);

            $this->audit->log(
                'workflow',
                'workflow.submitted',
                'Document submitted for approval',
                $instance,
                null,
                ['document_id' => $document->id],
                null,
                $document->organization_id,
                $actor
            );
            $this->notifyStepApprovers($instance, 'submitted');

            return $instance;
        });
    }

    public function approve(string $instanceId, User $actor, ?string $comments = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instanceId, $actor, $comments) {
            $instance = $this->repository->findInstance($instanceId);
            $this->assertInProgress($instance);
            $step = $this->assertCanAct($instance, $actor);

            $this->repository->createAction([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $step->id,
                'action' => WorkflowActionType::Approved->value,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'acted_at' => now(),
            ]);

            $next = $instance->workflow->steps
                ->sortBy('step_order')
                ->first(fn (WorkflowStep $s) => $s->step_order > $step->step_order);

            if ($next) {
                $instance = $this->repository->updateInstance($instance, [
                    'current_step_id' => $next->id,
                    'status' => WorkflowInstanceStatus::InProgress->value,
                ]);
            } else {
                $instance = $this->repository->updateInstance($instance, [
                    'current_step_id' => null,
                    'status' => WorkflowInstanceStatus::Approved->value,
                    'completed_at' => now(),
                ]);

                $instance->document->update([
                    'approval_status' => ApprovalStatus::Approved->value,
                ]);
            }

            $instance = $this->repository->findInstance($instance->id);
            $this->audit->log('workflow', 'workflow.approved', 'Workflow step approved', $instance, null, null, null, $instance->organization_id, $actor);

            if ($instance->status === WorkflowInstanceStatus::Approved) {
                $this->notifySubmitter($instance, 'approved');
            } elseif ($instance->currentStep) {
                $this->notifyStepApprovers($instance, 'submitted');
            }

            return $instance;
        });
    }

    public function reject(string $instanceId, User $actor, ?string $comments = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instanceId, $actor, $comments) {
            $instance = $this->repository->findInstance($instanceId);
            $this->assertInProgress($instance);
            $step = $this->assertCanAct($instance, $actor);

            $this->repository->createAction([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $step->id,
                'action' => WorkflowActionType::Rejected->value,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'acted_at' => now(),
            ]);

            $instance = $this->repository->updateInstance($instance, [
                'current_step_id' => null,
                'status' => WorkflowInstanceStatus::Rejected->value,
                'completed_at' => now(),
            ]);

            $instance->document->update([
                'approval_status' => ApprovalStatus::Rejected->value,
            ]);

            $instance = $this->repository->findInstance($instance->id);
            $this->audit->log('workflow', 'workflow.rejected', 'Document rejected', $instance, null, ['comments' => $comments], null, $instance->organization_id, $actor);
            $this->notifySubmitter($instance, 'rejected', $comments);

            return $instance;
        });
    }

    public function returnToSubmitter(string $instanceId, User $actor, ?string $comments = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instanceId, $actor, $comments) {
            $instance = $this->repository->findInstance($instanceId);
            $this->assertInProgress($instance);
            $step = $this->assertCanAct($instance, $actor);

            $this->repository->createAction([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $step->id,
                'action' => WorkflowActionType::Returned->value,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'acted_at' => now(),
            ]);

            $instance = $this->repository->updateInstance($instance, [
                'current_step_id' => null,
                'status' => WorkflowInstanceStatus::Returned->value,
                'completed_at' => now(),
            ]);

            $instance->document->update([
                'approval_status' => ApprovalStatus::Returned->value,
            ]);

            $instance = $this->repository->findInstance($instance->id);
            $this->audit->log('workflow', 'workflow.returned', 'Document returned', $instance, null, ['comments' => $comments], null, $instance->organization_id, $actor);
            $this->notifySubmitter($instance, 'returned', $comments);

            return $instance;
        });
    }

    public function cancel(string $instanceId, User $actor, ?string $comments = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instanceId, $actor, $comments) {
            $instance = $this->repository->findInstance($instanceId);

            if ($instance->status !== WorkflowInstanceStatus::InProgress) {
                throw ValidationException::withMessages([
                    'instance' => ['Only in-progress instances can be cancelled.'],
                ]);
            }

            $isSubmitter = $instance->submitted_by === $actor->id;
            $canManage = $actor->can('workflow.manage') || $actor->hasRole('super_admin');

            if (! $isSubmitter && ! $canManage) {
                throw ValidationException::withMessages([
                    'instance' => ['Only the submitter or a workflow manager can cancel.'],
                ]);
            }

            $this->repository->createAction([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $instance->current_step_id,
                'action' => WorkflowActionType::Cancelled->value,
                'actor_id' => $actor->id,
                'comments' => $comments,
                'acted_at' => now(),
            ]);

            $instance = $this->repository->updateInstance($instance, [
                'current_step_id' => null,
                'status' => WorkflowInstanceStatus::Cancelled->value,
                'completed_at' => now(),
            ]);

            $instance->document->update([
                'approval_status' => ApprovalStatus::Draft->value,
            ]);

            $instance = $this->repository->findInstance($instance->id);
            $this->audit->log('workflow', 'workflow.cancelled', 'Workflow cancelled', $instance, null, null, null, $instance->organization_id, $actor);
            $this->notifySubmitter($instance, 'cancelled', $comments);

            return $instance;
        });
    }

    public function inbox(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateInbox($user, $filters, $perPage);
    }

    public function instances(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateInstances($filters, $perPage);
    }

    public function showInstance(string $id): WorkflowInstance
    {
        return $this->repository->findInstance($id);
    }

    public function documentStatus(string $documentId): array
    {
        $active = $this->repository->findActiveInstanceForDocument($documentId);
        $history = $this->repository->paginateInstances(['document_id' => $documentId], 20);

        return [
            'active_instance' => $active,
            'history' => $history->items(),
        ];
    }

    public function stats(string $organizationId, User $user): array
    {
        return $this->repository->stats($organizationId, $user);
    }

    public function recentActions(string $organizationId, int $limit = 10): Collection
    {
        return $this->repository->recentActions($organizationId, $limit);
    }

    public function userCanActOnStep(User $user, WorkflowStep $step): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($step->role_id && $user->roles->contains('id', $step->role_id)) {
            return true;
        }

        return $step->approvers->contains('id', $user->id);
    }

    private function assertInProgress(WorkflowInstance $instance): void
    {
        if ($instance->status !== WorkflowInstanceStatus::InProgress) {
            throw ValidationException::withMessages([
                'instance' => ['This workflow instance is not awaiting approval.'],
            ]);
        }
    }

    private function assertCanAct(WorkflowInstance $instance, User $actor): WorkflowStep
    {
        $step = $instance->currentStep;
        if (! $step) {
            throw ValidationException::withMessages([
                'instance' => ['No current approval step.'],
            ]);
        }

        $step->loadMissing(['role', 'approvers']);
        $actor->loadMissing('roles');

        if (! $this->userCanActOnStep($actor, $step)) {
            throw ValidationException::withMessages([
                'instance' => ['You are not an assigned approver for this step.'],
            ]);
        }

        return $step;
    }

    private function assertValidSteps(array $steps): void
    {
        if ($steps === []) {
            throw ValidationException::withMessages([
                'steps' => ['At least one approval step is required.'],
            ]);
        }

        foreach ($steps as $index => $step) {
            $hasRole = ! empty($step['role_id']);
            $hasUsers = ! empty($step['approver_user_ids']);

            if (! $hasRole && ! $hasUsers) {
                throw ValidationException::withMessages([
                    "steps.{$index}" => ['Each step needs a role and/or specific approver users.'],
                ]);
            }
        }
    }

    private function notifySubmitter(WorkflowInstance $instance, string $event, ?string $message = null): void
    {
        $instance->loadMissing(['submitter', 'document']);
        if ($instance->submitter) {
            $instance->submitter->notify(new WorkflowActivityNotification($event, $instance, $message));
        }
    }

    private function notifyStepApprovers(WorkflowInstance $instance, string $event): void
    {
        $instance->loadMissing(['currentStep.role', 'currentStep.approvers', 'document']);
        $step = $instance->currentStep;
        if (! $step) {
            return;
        }

        $recipients = collect($step->approvers);
        if ($step->role_id && $step->role) {
            $recipients = $recipients->merge(User::role($step->role->name)->get());
        }

        $recipients = $recipients->unique('id')->filter();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new WorkflowActivityNotification($event, $instance));
        }
    }
}
