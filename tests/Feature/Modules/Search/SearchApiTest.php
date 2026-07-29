<?php

namespace Tests\Feature\Modules\Search;

use App\Models\User;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Organization\Models\Organization;
use App\Modules\Search\Models\SavedSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchApiTest extends TestCase
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

    public function test_can_search_documents_by_query_and_filters(): void
    {
        $this->upload('Security Policy Handbook', 'SEC-100');
        $this->upload('Leave Form', 'HR-200');

        $this->getJson('/api/v1/search/documents?'.http_build_query([
            'organization_id' => $this->organization->id,
            'q' => 'Security Policy',
        ]))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.title', 'Security Policy Handbook');

        $this->getJson('/api/v1/search/documents?'.http_build_query([
            'organization_id' => $this->organization->id,
            'approval_status' => ApprovalStatus::Draft->value,
        ]))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    }

    public function test_facets_endpoint(): void
    {
        $this->upload('Alpha');
        $this->upload('Beta');

        $this->getJson('/api/v1/search/facets?organization_id='.$this->organization->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'approval_status',
                    'status',
                    'confidentiality',
                    'extension',
                ],
            ]);
    }

    public function test_saved_search_crud_and_apply_criteria(): void
    {
        $this->upload('Contract Draft');

        $saved = $this->postJson('/api/v1/search/saved', [
            'organization_id' => $this->organization->id,
            'name' => 'Contracts',
            'description' => 'Find contracts',
            'is_shared' => false,
            'criteria' => [
                'q' => 'Contract',
                'approval_status' => 'draft',
            ],
        ])->assertCreated()
            ->json('data');

        $this->getJson('/api/v1/search/saved?organization_id='.$this->organization->id)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Contracts');

        $this->putJson('/api/v1/search/saved/'.$saved['id'], [
            'name' => 'Contract files',
            'is_shared' => true,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Contract files')
            ->assertJsonPath('data.is_shared', true);

        $this->getJson('/api/v1/search/documents?'.http_build_query([
            'organization_id' => $this->organization->id,
            ...(SavedSearch::query()->findOrFail($saved['id'])->criteria),
        ]))->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->deleteJson('/api/v1/search/saved/'.$saved['id'])
            ->assertOk();

        $this->assertSoftDeleted('saved_searches', ['id' => $saved['id']]);
    }

    public function test_search_requires_permission(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/search/documents?organization_id='.$this->organization->id)
            ->assertForbidden();
    }

    private function upload(string $title, ?string $reference = null): void
    {
        $file = UploadedFile::fake()->create(str_replace(' ', '_', $title).'.pdf', 40, 'application/pdf');

        $this->post('/api/v1/documents', [
            'organization_id' => $this->organization->id,
            'title' => $title,
            'reference_no' => $reference,
            'file' => $file,
        ], ['Accept' => 'application/json'])->assertCreated();
    }
}
