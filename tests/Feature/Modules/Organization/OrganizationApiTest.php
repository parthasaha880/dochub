<?php

namespace Tests\Feature\Modules\Organization;

use App\Models\User;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_admin_can_create_and_list_organizations(): void
    {
        $this->actingAsAdmin();

        $create = $this->postJson('/api/v1/organizations', [
            'code' => 'GOV01',
            'name' => 'Ministry of Records',
            'city' => 'Dhaka',
            'is_active' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'GOV01');

        $this->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_department_under_organization(): void
    {
        $this->actingAsAdmin();

        $org = Organization::factory()->create(['code' => 'BANK1']);

        $response = $this->postJson('/api/v1/departments', [
            'organization_id' => $org->id,
            'code' => 'FIN',
            'name' => 'Finance',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'FIN');

        $this->assertDatabaseHas('departments', [
            'organization_id' => $org->id,
            'code' => 'FIN',
        ]);
    }

    public function test_organization_tree_endpoint_returns_hierarchy(): void
    {
        $this->actingAsAdmin();

        $org = Organization::factory()->create();
        Department::query()->create([
            'organization_id' => $org->id,
            'code' => 'HR',
            'name' => 'Human Resources',
            'is_active' => true,
        ]);

        $this->getJson("/api/v1/organizations/{$org->id}/tree")
            ->assertOk()
            ->assertJsonPath('data.type', 'organization')
            ->assertJsonPath('data.code', $org->code);
    }

    public function test_unauthenticated_user_cannot_access_organizations(): void
    {
        $this->getJson('/api/v1/organizations')->assertUnauthorized();
    }
}
