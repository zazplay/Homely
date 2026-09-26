<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

describe('register', function () {
    it('creates a client by default and returns a token', function () {
        postJson('/api/auth/register', [
            'name' => 'Viktor',
            'email' => 'viktor@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])
            ->assertCreated()
            ->assertJsonPath('user.email', 'viktor@example.com')
            ->assertJsonPath('user.role', 'client')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role', 'created_at'], 'token']);
    });

    it('allows registering as a realtor', function () {
        postJson('/api/auth/register', [
            'name' => 'Anna',
            'email' => 'anna@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'realtor',
        ])->assertCreated()->assertJsonPath('user.role', 'realtor');
    });

    it('does not allow self-assigning admin', function () {
        postJson('/api/auth/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors('role');

        expect(User::where('email', 'hacker@example.com')->exists())->toBeFalse();
    });

    it('validates input', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        postJson('/api/auth/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });
});

describe('login', function () {
    it('returns a token for valid credentials', function () {
        $user = User::factory()->create();

        postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['user', 'token']);
    });

    it('rejects a wrong password', function () {
        $user = User::factory()->create();

        postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('is rate limited', function () {
        foreach (range(1, 6) as $_) {
            postJson('/api/auth/login', ['email' => 'x@example.com', 'password' => 'x']);
        }

        postJson('/api/auth/login', ['email' => 'x@example.com', 'password' => 'x'])->assertTooManyRequests();
    });
});

describe('me', function () {
    it('requires a token', function () {
        getJson('/api/auth/me')->assertUnauthorized();
    });

    it('returns the current user', function () {
        $user = User::factory()->admin()->create();
        Sanctum::actingAs($user);

        getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('role', 'admin');
    });
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    postJson('/api/auth/logout', [], ['Authorization' => "Bearer {$token}"])->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
