<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Organization\Models\Organization;
use App\Modules\Workflow\Enums\WorkflowInstanceStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowInstance;
use App\Modules\Workflow\Services\WorkflowService;
use Illuminate\Database\Seeder;

class WorkflowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('code', 'EDAMS')->first();
        $admin = User::query()->where('email', 'admin@edams.local')->first()
            ?? User::query()->where('email', 'parthasaha31@gmail.com')->first();

        if (! $org || ! $admin) {
            return;
        }

        $approverIds = User::query()
            ->whereIn('email', [
                'admin@edams.local',
                'parthasaha31@gmail.com',
                'jahid@kormo.bd',
            ])
            ->pluck('id')
            ->all();

        if ($approverIds === []) {
            $approverIds = [$admin->id];
        }

        $workflow = Workflow::query()
            ->where('organization_id', $org->id)
            ->where('code', 'STD-2LVL')
            ->first();

        if (! $workflow) {
            $managerRole = Role::findByName('manager', 'web');
            $orgAdminRole = Role::findByName('organization_admin', 'web');

            $workflow = app(WorkflowService::class)->createWorkflow([
                'organization_id' => $org->id,
                'name' => 'Standard Two-Level Approval',
                'code' => 'STD-2LVL',
                'description' => 'Sequential: Manager review, then Organization Admin final approval.',
                'is_active' => true,
                'is_default' => true,
                'steps' => [
                    [
                        'step_order' => 1,
                        'name' => 'Manager Review',
                        'description' => 'First-level departmental / managerial review',
                        'role_id' => $managerRole?->id,
                        'approver_user_ids' => $approverIds,
                    ],
                    [
                        'step_order' => 2,
                        'name' => 'Organization Admin Approval',
                        'description' => 'Final organizational approval',
                        'role_id' => $orgAdminRole?->id,
                        'approver_user_ids' => $approverIds,
                    ],
                ],
            ], $admin);
        }

        // Demo documents were often marked under_review without a workflow instance.
        $openDocumentIds = WorkflowInstance::query()
            ->where('status', WorkflowInstanceStatus::InProgress->value)
            ->pluck('document_id');

        Document::query()
            ->where('organization_id', $org->id)
            ->where('approval_status', ApprovalStatus::UnderReview->value)
            ->whereNotIn('id', $openDocumentIds)
            ->update(['approval_status' => ApprovalStatus::Draft->value]);

        // Seed a few real inbox items if none exist yet.
        $pending = WorkflowInstance::query()
            ->where('organization_id', $org->id)
            ->where('status', WorkflowInstanceStatus::InProgress->value)
            ->count();

        if ($pending > 0) {
            return;
        }

        $service = app(WorkflowService::class);
        $docs = Document::query()
            ->where('organization_id', $org->id)
            ->where('approval_status', ApprovalStatus::Draft->value)
            ->latest()
            ->limit(5)
            ->get();

        foreach ($docs as $document) {
            try {
                $service->submitDocument($document->id, $admin, $workflow->id, 'Demo submission for approval inbox');
            } catch (\Throwable $e) {
                // Skip documents that cannot be submitted (missing file, policy, etc.).
                report($e);
            }
        }
    }
}
