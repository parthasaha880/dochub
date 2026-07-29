<?php

namespace Tests\Feature\Modules\Operations;

use App\Models\User;
use App\Modules\Organization\Models\Organization;
use App\Modules\Sharing\Models\DocumentShare;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnterpriseOpsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        Sanctum::actingAs($this->admin);
        $this->organization = Organization::factory()->create();
    }

    public function test_audit_log_records_document_upload(): void
    {
        $this->upload('Audit Doc');

        $this->getJson('/api/v1/audit-logs?organization_id='.$this->organization->id)
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.action', 'document.uploaded');
    }

    public function test_share_create_and_public_download(): void
    {
        $doc = $this->upload('Shared Doc');

        $share = $this->postJson('/api/v1/shares', [
            'document_id' => $doc['id'],
            'share_type' => 'external',
            'label' => 'Public link',
        ])->assertCreated()
            ->json('data');

        $this->getJson('/api/v1/public/shares/'.$share['token'])
            ->assertOk()
            ->assertJsonPath('data.document_title', 'Shared Doc');

        $this->get('/api/v1/public/shares/'.$share['token'].'/download')
            ->assertOk();

        $this->assertDatabaseHas('document_shares', [
            'id' => $share['id'],
            'download_count' => 1,
        ]);
    }

    public function test_retention_policy_and_run(): void
    {
        $this->postJson('/api/v1/retention/policies', [
            'organization_id' => $this->organization->id,
            'name' => '1 year archive',
            'code' => 'Y1-ARC',
            'retention_days' => 365,
            'action_on_expiry' => 'archive',
            'is_active' => true,
        ])->assertCreated();

        $this->postJson('/api/v1/retention/run', [
            'organization_id' => $this->organization->id,
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_reports_preview_and_export(): void
    {
        $this->upload('Report Doc');

        $this->getJson('/api/v1/reports/preview?organization_id='.$this->organization->id.'&type=inventory')
            ->assertOk()
            ->assertJsonPath('data.count', 1);

        $this->get('/api/v1/reports/export?organization_id='.$this->organization->id.'&type=inventory')
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_notifications_endpoints(): void
    {
        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data', 'unread_count']]);
    }

    private function upload(string $title): array
    {
        $file = UploadedFile::fake()->create(str_replace(' ', '_', $title).'.pdf', 30, 'application/pdf');

        return $this->post('/api/v1/documents', [
            'organization_id' => $this->organization->id,
            'title' => $title,
            'file' => $file,
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');
    }
}
