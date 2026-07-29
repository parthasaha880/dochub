<?php

namespace Tests\Feature\Modules\Dashboard;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        Sanctum::actingAs($this->admin);

        $this->organization = Organization::factory()->create();
    }

    public function test_summary_requires_organization(): void
    {
        $this->getJson('/api/v1/dashboard/summary')
            ->assertStatus(422);
    }

    public function test_summary_returns_kpis_trends_and_breakdowns(): void
    {
        $this->createDocument('Policy A');
        $this->createDocument('Policy B');

        $this->getJson('/api/v1/dashboard/summary?organization_id='.$this->organization->id.'&days=30')
            ->assertOk()
            ->assertJsonPath('data.kpis.documents_total', 2)
            ->assertJsonStructure([
                'data' => [
                    'range_days',
                    'kpis' => [
                        'documents_total',
                        'pending_my_approvals',
                        'storage_bytes',
                        'documents_under_review',
                    ],
                    'trends' => ['labels', 'uploads', 'submissions', 'approvals'],
                    'breakdowns' => ['approval_status', 'extension', 'workflow_status'],
                    'storage_reports' => [
                        'by_document_type',
                        'by_file_category',
                        'by_department',
                        'by_user',
                        'disk' => ['quota_bytes', 'used_bytes', 'free_bytes', 'rows'],
                    ],
                    'recent_documents',
                    'recent_actions',
                ],
            ]);
    }

    public function test_viewer_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        // no dashboard.view
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard/summary?organization_id='.$this->organization->id)
            ->assertForbidden();
    }

    private function createDocument(string $title): Document
    {
        $file = UploadedFile::fake()->create($title.'.pdf', 50, 'application/pdf');

        $payload = $this->post('/api/v1/documents', [
            'organization_id' => $this->organization->id,
            'title' => $title,
            'file' => $file,
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');

        return Document::query()->findOrFail($payload['id']);
    }
}
