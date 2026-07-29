<?php

namespace Tests\Feature\Modules\Users;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementApiTest extends TestCase
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

    public function test_admin_can_create_user_with_roles(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Officer One',
            'email' => 'officer1@edams.local',
            'password' => 'Password@12345',
            'password_confirmation' => 'Password@12345',
            'is_active' => true,
            'roles' => ['officer'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'officer1@edams.local');

        $created = User::query()->where('email', 'officer1@edams.local')->first();
        $this->assertTrue($created->hasRole('officer'));
    }

    public function test_admin_can_create_custom_role_with_permissions(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/roles', [
            'name' => 'records_clerk',
            'description' => 'Custom clerk role',
            'hierarchy_level' => 55,
            'permissions' => ['users.view', 'organization.view'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'records_clerk');

        $role = Role::findByName('records_clerk', 'web');
        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertFalse($role->is_system);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $role = Role::findByName('viewer', 'web');

        $this->deleteJson("/api/v1/roles/{$role->id}")
            ->assertStatus(422);
    }

    public function test_admin_can_list_permission_groups(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/permissions/groups')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(
            Permission::query()->where('group', 'users')->exists()
        );
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $admin = $this->actingAsAdmin();

        $this->deleteJson("/api/v1/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
