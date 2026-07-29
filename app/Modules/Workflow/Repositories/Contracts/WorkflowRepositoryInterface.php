<?php

namespace App\Modules\Workflow\Repositories\Contracts;

use App\Models\User;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowInstance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface WorkflowRepositoryInterface
{
    public function paginateWorkflows(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function findWorkflow(string $id): Workflow;

    public function createWorkflow(array $data): Workflow;

    public function updateWorkflow(Workflow $workflow, array $data): Workflow;

    public function deleteWorkflow(Workflow $workflow): void;

    public function clearDefaultForOrganization(string $organizationId, ?string $exceptWorkflowId = null): void;

    public function findDefaultWorkflow(string $organizationId, ?string $categoryId = null): ?Workflow;

    public function syncSteps(Workflow $workflow, array $steps): Workflow;

    public function createInstance(array $data): WorkflowInstance;

    public function findInstance(string $id): WorkflowInstance;

    public function updateInstance(WorkflowInstance $instance, array $data): WorkflowInstance;

    public function createAction(array $data): void;

    public function findActiveInstanceForDocument(string $documentId): ?WorkflowInstance;

    public function paginateInbox(User $user, array $filters, int $perPage = 15): LengthAwarePaginator;

    public function paginateInstances(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function recentActions(string $organizationId, int $limit = 10): Collection;

    public function stats(string $organizationId, ?User $user = null): array;
}
