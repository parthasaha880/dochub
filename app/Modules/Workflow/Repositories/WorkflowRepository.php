<?php

namespace App\Modules\Workflow\Repositories;

use App\Models\User;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Workflow\Enums\WorkflowInstanceStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowAction;
use App\Modules\Workflow\Models\WorkflowInstance;
use App\Modules\Workflow\Models\WorkflowStep;
use App\Modules\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    public function paginateWorkflows(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Workflow::query()
            ->with(['steps.role', 'steps.approvers', 'category'])
            ->when($filters['organization_id'] ?? null, fn ($q, $id) => $q->where('organization_id', $id))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findWorkflow(string $id): Workflow
    {
        return Workflow::query()
            ->with(['steps.role', 'steps.approvers', 'category'])
            ->findOrFail($id);
    }

    public function createWorkflow(array $data): Workflow
    {
        return Workflow::query()->create($data);
    }

    public function updateWorkflow(Workflow $workflow, array $data): Workflow
    {
        $workflow->update($data);

        return $workflow->fresh(['steps.role', 'steps.approvers', 'category']);
    }

    public function deleteWorkflow(Workflow $workflow): void
    {
        $workflow->delete();
    }

    public function clearDefaultForOrganization(string $organizationId, ?string $exceptWorkflowId = null): void
    {
        Workflow::query()
            ->where('organization_id', $organizationId)
            ->when($exceptWorkflowId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->update(['is_default' => false]);
    }

    public function findDefaultWorkflow(string $organizationId, ?string $categoryId = null): ?Workflow
    {
        if ($categoryId) {
            $categoryDefault = Workflow::query()
                ->with(['steps.role', 'steps.approvers'])
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->where('category_id', $categoryId)
                ->where('is_default', true)
                ->first();

            if ($categoryDefault) {
                return $categoryDefault;
            }
        }

        $orgDefault = Workflow::query()
            ->with(['steps.role', 'steps.approvers'])
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->whereNull('category_id')
            ->first();

        if ($orgDefault) {
            return $orgDefault;
        }

        $anyDefault = Workflow::query()
            ->with(['steps.role', 'steps.approvers'])
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($anyDefault) {
            return $anyDefault;
        }

        return Workflow::query()
            ->with(['steps.role', 'steps.approvers'])
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->first();
    }

    public function syncSteps(Workflow $workflow, array $steps): Workflow
    {
        return DB::transaction(function () use ($workflow, $steps) {
            $workflow->steps()->each(function (WorkflowStep $step) {
                $step->approvers()->detach();
                $step->delete();
            });

            foreach ($steps as $index => $stepData) {
                $step = $workflow->steps()->create([
                    'step_order' => $stepData['step_order'] ?? ($index + 1),
                    'name' => $stepData['name'],
                    'description' => $stepData['description'] ?? null,
                    'role_id' => $stepData['role_id'] ?? null,
                ]);

                $userIds = array_values(array_unique(array_filter($stepData['approver_user_ids'] ?? [])));
                if ($userIds !== []) {
                    $step->approvers()->sync($userIds);
                }
            }

            return $this->findWorkflow($workflow->id);
        });
    }

    public function createInstance(array $data): WorkflowInstance
    {
        return WorkflowInstance::query()->create($data);
    }

    public function findInstance(string $id): WorkflowInstance
    {
        return WorkflowInstance::query()
            ->with([
                'document',
                'workflow.steps.role',
                'workflow.steps.approvers',
                'currentStep.role',
                'currentStep.approvers',
                'submitter',
                'actions.actor',
                'actions.step',
            ])
            ->findOrFail($id);
    }

    public function updateInstance(WorkflowInstance $instance, array $data): WorkflowInstance
    {
        $instance->update($data);

        return $instance->fresh([
            'document',
            'workflow.steps.role',
            'workflow.steps.approvers',
            'currentStep.role',
            'currentStep.approvers',
            'submitter',
            'actions.actor',
            'actions.step',
        ]);
    }

    public function createAction(array $data): void
    {
        WorkflowAction::query()->create($data);
    }

    public function findActiveInstanceForDocument(string $documentId): ?WorkflowInstance
    {
        return WorkflowInstance::query()
            ->with(['currentStep.role', 'currentStep.approvers', 'workflow.steps'])
            ->where('document_id', $documentId)
            ->whereIn('status', [
                WorkflowInstanceStatus::InProgress->value,
                WorkflowInstanceStatus::Returned->value,
            ])
            ->latest('submitted_at')
            ->first();
    }

    public function paginateInbox(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $roleIds = $user->roles()->pluck('id')->all();
        $isSuperAdmin = $user->hasRole('super_admin');

        return WorkflowInstance::query()
            ->with(['document', 'workflow', 'currentStep.role', 'currentStep.approvers', 'submitter'])
            ->where('status', WorkflowInstanceStatus::InProgress->value)
            ->whereNotNull('current_step_id')
            ->when($filters['organization_id'] ?? null, fn ($q, $id) => $q->where('organization_id', $id))
            ->when(! $isSuperAdmin, function ($q) use ($user, $roleIds) {
                $q->whereHas('currentStep', function ($stepQuery) use ($user, $roleIds) {
                    $stepQuery->where(function ($inner) use ($user, $roleIds) {
                        $inner->whereIn('role_id', $roleIds)
                            ->orWhereHas('approvers', fn ($a) => $a->where('users.id', $user->id));
                    });
                });
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('document', function ($d) use ($search) {
                    $d->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('submitted_at')
            ->paginate($perPage);
    }

    public function paginateInstances(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return WorkflowInstance::query()
            ->with(['document', 'workflow', 'currentStep', 'submitter'])
            ->when($filters['organization_id'] ?? null, fn ($q, $id) => $q->where('organization_id', $id))
            ->when($filters['document_id'] ?? null, fn ($q, $id) => $q->where('document_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['workflow_id'] ?? null, fn ($q, $id) => $q->where('workflow_id', $id))
            ->orderByDesc('submitted_at')
            ->paginate($perPage);
    }

    public function recentActions(string $organizationId, int $limit = 10): Collection
    {
        return WorkflowAction::query()
            ->with(['actor', 'step', 'instance.document'])
            ->whereHas('instance', fn ($q) => $q->where('organization_id', $organizationId))
            ->orderByDesc('acted_at')
            ->limit($limit)
            ->get();
    }

    public function stats(string $organizationId, ?User $user = null): array
    {
        $base = WorkflowInstance::query()->where('organization_id', $organizationId);

        $inboxCount = 0;
        if ($user) {
            $inboxCount = $this->paginateInbox($user, ['organization_id' => $organizationId], 1)->total();
        }

        return [
            'workflows_active' => Workflow::query()
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->count(),
            'instances_in_progress' => (clone $base)->where('status', WorkflowInstanceStatus::InProgress->value)->count(),
            'instances_approved' => (clone $base)->where('status', WorkflowInstanceStatus::Approved->value)->count(),
            'instances_rejected' => (clone $base)->where('status', WorkflowInstanceStatus::Rejected->value)->count(),
            'instances_returned' => (clone $base)->where('status', WorkflowInstanceStatus::Returned->value)->count(),
            'pending_my_approvals' => $inboxCount,
            'documents_draft' => Document::query()
                ->where('organization_id', $organizationId)
                ->where('approval_status', ApprovalStatus::Draft->value)
                ->count(),
            'documents_under_review' => Document::query()
                ->where('organization_id', $organizationId)
                ->where('approval_status', ApprovalStatus::UnderReview->value)
                ->count(),
            'documents_approved' => Document::query()
                ->where('organization_id', $organizationId)
                ->where('approval_status', ApprovalStatus::Approved->value)
                ->count(),
        ];
    }
}
