<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Modules\Organization\Models\Organization;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Services\WorkflowService;
use App\Models\User;
use Illuminate\Database\Seeder;

class WorkflowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('code', 'EDAMS')->first();
        $admin = User::query()->where('email', 'admin@edams.local')->first();

        if (! $org || ! $admin) {
            return;
        }

        if (Workflow::query()->where('organization_id', $org->id)->where('code', 'STD-2LVL')->exists()) {
            return;
        }

        $managerRole = Role::findByName('manager', 'web');
        $orgAdminRole = Role::findByName('organization_admin', 'web');

        app(WorkflowService::class)->createWorkflow([
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
                    'approver_user_ids' => [$admin->id],
                ],
                [
                    'step_order' => 2,
                    'name' => 'Organization Admin Approval',
                    'description' => 'Final organizational approval',
                    'role_id' => $orgAdminRole?->id,
                    'approver_user_ids' => [$admin->id],
                ],
            ],
        ], $admin);
    }
}
