<?php

namespace Tests\Feature\Modules\Workflow;

use App\Models\Role;
use App\Models\User;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Organization\Models\Organization;
use App\Modules\Workflow\Enums\WorkflowInstanceStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    private Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        Sanctum::actingAs($this->admin);

        $this->organization = Organization::factory()->create();
        $this->managerRole = Role::findByName('manager', 'web');
    }

    public function test_can_create_sequential_workflow_and_run_approval(): void
    {
        $workflow = $this->postJson('/api/v1/workflows', [
            'organization_id' => $this->organization->id,
            'name' => 'Two Level',
            'code' => 'TWO',
            'is_active' => true,
            'is_default' => true,
            'steps' => [
                [
                    'step_order' => 1,
                    'name' => 'Manager',
                    'role_id' => $this->managerRole->id,
                    'approver_user_ids' => [$this->admin->id],
                ],
                [
                    'step_order' => 2,
                    'name' => 'Final',
                    'approver_user_ids' => [$this->admin->id],
                ],
            ],
        ])->assertCreated()
            ->json('data');

        $this->assertCount(2, $workflow['steps']);

        $document = $this->createDocument();

        $instance = $this->postJson('/api/v1/workflows/submit', [
            'document_id' => $document->id,
            'workflow_id' => $workflow['id'],
            'note' => 'Please review',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'in_progress')
            ->json('data');

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'approval_status' => ApprovalStatus::UnderReview->value,
        ]);

        $this->postJson("/api/v1/workflows/instances/{$instance['id']}/approve", [
            'comments' => 'L1 ok',
        ])->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->postJson("/api/v1/workflows/instances/{$instance['id']}/approve", [
            'comments' => 'Final ok',
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'approval_status' => ApprovalStatus::Approved->value,
        ]);
    }

    public function test_reject_and_resubmit_flow(): void
    {
        $workflow = $this->createWorkflow();
        $document = $this->createDocument();

        $instance = $this->postJson('/api/v1/workflows/submit', [
            'document_id' => $document->id,
            'workflow_id' => $workflow->id,
        ])->assertCreated()->json('data');

        $this->postJson("/api/v1/workflows/instances/{$instance['id']}/reject", [
            'comments' => 'Needs changes',
        ])->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'approval_status' => ApprovalStatus::Rejected->value,
        ]);

        $this->postJson('/api/v1/workflows/submit', [
            'document_id' => $document->id,
            'workflow_id' => $workflow->id,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_return_to_submitter(): void
    {
        $workflow = $this->createWorkflow();
        $document = $this->createDocument();

        $instance = $this->postJson('/api/v1/workflows/submit', [
            'document_id' => $document->id,
            'workflow_id' => $workflow->id,
        ])->assertCreated()->json('data');

        $this->postJson("/api/v1/workflows/instances/{$instance['id']}/return", [
            'comments' => 'Fix metadata',
        ])->assertOk()
            ->assertJsonPath('data.status', WorkflowInstanceStatus::Returned->value);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'approval_status' => ApprovalStatus::Returned->value,
        ]);
    }

    public function test_inbox_lists_pending_for_approver(): void
    {
        $workflow = $this->createWorkflow();
        $document = $this->createDocument();

        $this->postJson('/api/v1/workflows/submit', [
            'document_id' => $document->id,
            'workflow_id' => $workflow->id,
        ])->assertCreated();

        $this->getJson('/api/v1/workflows/inbox?organization_id='.$this->organization->id)
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_cannot_delete_workflow_with_open_instance(): void
    {
        $workflow = $this->createWorkflow();
        $document = $this->createDocument();

        $this->postJson('/api/v1/workflows/submit', [
            'document_id' => $document->id,
            'workflow_id' => $workflow->id,
        ])->assertCreated();

        $this->deleteJson("/api/v1/workflows/{$workflow->id}")
            ->assertStatus(422);
    }

    public function test_stats_endpoint_returns_counts(): void
    {
        $this->getJson('/api/v1/workflows/stats?organization_id='.$this->organization->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'workflows_active',
                    'instances_in_progress',
                    'pending_my_approvals',
                    'documents_draft',
                ],
            ]);
    }

    private function createWorkflow(): Workflow
    {
        $workflow = Workflow::query()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Demo',
            'code' => 'DEMO-'.uniqid(),
            'is_active' => true,
            'is_default' => true,
            'created_by' => $this->admin->id,
        ]);

        $step = $workflow->steps()->create([
            'step_order' => 1,
            'name' => 'Review',
            'role_id' => $this->managerRole->id,
        ]);
        $step->approvers()->sync([$this->admin->id]);

        return $workflow->load(['steps.approvers']);
    }

    private function createDocument(): Document
    {
        $file = UploadedFile::fake()->create('memo.pdf', 40, 'application/pdf');

        $payload = $this->post('/api/v1/documents', [
            'organization_id' => $this->organization->id,
            'title' => 'Memo '.uniqid(),
            'file' => $file,
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');

        return Document::query()->findOrFail($payload['id']);
    }
}
