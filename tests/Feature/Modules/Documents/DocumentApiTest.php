<?php

namespace Tests\Feature\Modules\Documents;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\Folder;
use App\Modules\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentApiTest extends TestCase
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

    public function test_admin_can_create_folder_and_upload_document(): void
    {
        $folder = $this->postJson('/api/v1/folders', [
            'organization_id' => $this->organization->id,
            'name' => 'Contracts',
        ])->assertCreated()
            ->json('data');

        $file = UploadedFile::fake()->create('policy.pdf', 120, 'application/pdf');

        $response = $this->post('/api/v1/documents', [
            'organization_id' => $this->organization->id,
            'folder_id' => $folder['id'],
            'title' => 'Security Policy',
            'reference_no' => 'REF-001',
            'file' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Security Policy')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('documents', [
            'title' => 'Security Policy',
            'folder_id' => $folder['id'],
        ]);

        $this->assertDatabaseCount('document_versions', 1);
    }

    public function test_document_check_out_and_check_in(): void
    {
        $document = $this->createDocument();

        $this->postJson("/api/v1/documents/{$document->id}/check-out")
            ->assertOk()
            ->assertJsonPath('data.is_locked', true);

        $this->postJson("/api/v1/documents/{$document->id}/check-in")
            ->assertOk()
            ->assertJsonPath('data.checked_out_by', null);
    }

    public function test_soft_delete_restore_and_permanent_delete(): void
    {
        $document = $this->createDocument();

        $this->deleteJson("/api/v1/documents/{$document->id}")
            ->assertOk();

        $this->assertSoftDeleted('documents', ['id' => $document->id]);

        $this->postJson("/api/v1/documents/{$document->id}/restore")
            ->assertOk();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'deleted_at' => null,
        ]);

        $this->deleteJson("/api/v1/documents/{$document->id}");
        $this->deleteJson("/api/v1/documents/{$document->id}/force")
            ->assertOk();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_replace_file_increments_version(): void
    {
        $document = $this->createDocument();

        $file = UploadedFile::fake()->create('policy-v2.pdf', 200, 'application/pdf');

        $this->post("/api/v1/documents/{$document->id}/replace", [
            'file' => $file,
            'change_summary' => 'Updated clauses',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseCount('document_versions', 2);
    }

    public function test_document_preview_streams_inline(): void
    {
        $document = $this->createDocument();

        $this->get("/api/v1/documents/{$document->id}/preview")
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_folder_lock_and_hide_cascade_to_documents(): void
    {
        $folder = $this->postJson('/api/v1/folders', [
            'organization_id' => $this->organization->id,
            'name' => 'Secure',
        ])->assertCreated()->json('data');

        $file = \Illuminate\Http\UploadedFile::fake()->create('secret.pdf', 40, 'application/pdf');
        $documentId = $this->post('/api/v1/documents', [
            'organization_id' => $this->organization->id,
            'folder_id' => $folder['id'],
            'title' => 'Secret Doc',
            'file' => $file,
        ], ['Accept' => 'application/json'])->json('data.id');

        $this->postJson("/api/v1/folders/{$folder['id']}/lock")
            ->assertOk();

        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'is_locked' => 1,
        ]);

        $this->postJson("/api/v1/folders/{$folder['id']}/hide")
            ->assertOk();

        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'is_hidden' => 1,
        ]);

        $this->postJson("/api/v1/folders/{$folder['id']}/unlock")->assertOk();
        $this->postJson("/api/v1/folders/{$folder['id']}/unhide")->assertOk();

        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'is_locked' => 0,
            'is_hidden' => 0,
        ]);
    }

    public function test_folder_rename_lock_hide_and_delete(): void
    {
        $folder = $this->postJson('/api/v1/folders', [
            'organization_id' => $this->organization->id,
            'name' => 'Archive',
        ])->assertCreated()->json('data');

        $this->postJson("/api/v1/folders/{$folder['id']}/rename", ['name' => 'Archives'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Archives');

        $this->postJson("/api/v1/folders/{$folder['id']}/lock")
            ->assertOk()
            ->assertJsonPath('data.is_locked', true);

        $this->postJson("/api/v1/folders/{$folder['id']}/rename", ['name' => 'Nope'])
            ->assertStatus(422);

        $this->postJson("/api/v1/folders/{$folder['id']}/unlock")
            ->assertOk()
            ->assertJsonPath('data.is_locked', false);

        $this->postJson("/api/v1/folders/{$folder['id']}/hide")
            ->assertOk()
            ->assertJsonPath('data.is_hidden', true);

        $this->getJson('/api/v1/folders/tree?organization_id='.$this->organization->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/folders/tree?organization_id='.$this->organization->id.'&include_hidden=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->postJson("/api/v1/folders/{$folder['id']}/unhide")
            ->assertOk()
            ->assertJsonPath('data.is_hidden', false);

        $this->deleteJson("/api/v1/folders/{$folder['id']}")
            ->assertOk();

        $this->assertSoftDeleted('folders', ['id' => $folder['id']]);
    }

    public function test_folder_tree_endpoint(): void
    {
        Folder::query()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Root A',
        ]);

        $this->getJson('/api/v1/folders/tree?organization_id='.$this->organization->id)
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    private function createDocument(): Document
    {
        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $id = $this->post('/api/v1/documents', [
            'organization_id' => $this->organization->id,
            'title' => 'Sample Doc',
            'file' => $file,
        ], ['Accept' => 'application/json'])->json('data.id');

        return Document::query()->findOrFail($id);
    }
}
