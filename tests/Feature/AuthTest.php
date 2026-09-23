<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Viktor',
            'email' => 'viktor@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'viktor@example.com')
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);
    }

    public function test_role_cannot_be_set_on_registration(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ])->assertCreated()->assertJsonPath('user.role', 'customer');

        $this->assertSame(UserRole::Customer, User::firstWhere('email', 'hacker@example.com')->role);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_current_user(): void
    {
        $user = User::factory()->admin()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
