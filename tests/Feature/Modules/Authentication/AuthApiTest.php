<?php

namespace Tests\Feature\Modules\Authentication;

use App\Models\User;
use App\Modules\Authentication\Enums\LoginStatus;
use App\Modules\Authentication\Models\LoginActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'officer@edams.local',
            'password' => Hash::make('Password@12345'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'officer@edams.local',
            'password' => 'Password@12345',
            'remember' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'officer@edams.local')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertDatabaseHas('login_activities', [
            'user_id' => $user->id,
            'status' => LoginStatus::Success->value,
        ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'officer@edams.local',
            'password' => Hash::make('Password@12345'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'officer@edams.local',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('login_activities', [
            'email' => 'officer@edams.local',
            'status' => LoginStatus::Failed->value,
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@edams.local',
            'password' => Hash::make('Password@12345'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@edams.local',
            'password' => 'Password@12345',
        ])->assertStatus(422);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(
            LoginActivity::query()
                ->where('user_id', $user->id)
                ->where('status', LoginStatus::Logout->value)
                ->exists()
        );
    }

    public function test_user_can_list_login_activities(): void
    {
        $user = User::factory()->create();
        LoginActivity::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => LoginStatus::Success->value,
            'ip_address' => '127.0.0.1',
            'logged_in_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/login-activities')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
