<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function register_creates_a_customer_and_returns_a_bearer_token(): void
    {
        $this->freezeTime();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->postJson('/api/v1/auth/register', [
            'device_name' => 'Test device',
            'email' => 'customer@example.test',
            'name' => 'Test Customer',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated()->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'customer@example.test')
            ->assertJsonPath('expires_at', now()->addMinutes(43200)->toISOString());

        $user = User::query()->where('email', 'customer@example.test')->firstOrFail();
        self::assertTrue($user->hasRole(UserRole::Customer->value));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Test device', 'tokenable_id' => $user->id]);
    }

    #[Test]
    public function register_returns_422_for_invalid_or_duplicate_email(): void
    {
        User::factory()->create(['email' => 'customer@example.test']);

        $this->postJson('/api/v1/auth/register', [
            'email' => 'customer@example.test',
            'name' => 'Test Customer',
            'password' => 'secret-password',
            'password_confirmation' => 'different-password',
        ])->assertUnprocessable()->assertJsonPath('errors.0.pointer', '#/email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function login_returns_a_bearer_token_for_valid_credentials(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Test device',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('expires_at', now()->addMinutes(43200)->toISOString());

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Test device',
        ]);
    }

    #[Test]
    public function login_returns_401_for_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'invalid-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('detail', 'Authentication failed.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test device');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    #[Test]
    public function logout_revokes_the_current_access_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test device');

        $this->withToken($token->plainTextToken)
            ->deleteJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    #[Test]
    public function tokens_returns_the_authenticated_users_tokens(): void
    {
        $user = User::factory()->create();
        $firstToken = $user->createToken('First device');
        $secondToken = $user->createToken('Second device');

        $this->withToken($secondToken->plainTextToken)
            ->getJson('/api/v1/auth/tokens')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $firstToken->accessToken->id])
            ->assertJsonFragment(['id' => $secondToken->accessToken->id]);
    }

    #[Test]
    public function destroy_token_revokes_an_owned_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('Current device');
        $revokedToken = $user->createToken('Other device');

        $this->withToken($currentToken->plainTextToken)
            ->deleteJson('/api/v1/auth/tokens/'.$revokedToken->accessToken->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $revokedToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
    }

    #[Test]
    public function destroy_token_returns_404_for_another_users_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('Current device');
        $otherToken = User::factory()->create()->createToken('Other device');

        $this->withToken($currentToken->plainTextToken)
            ->deleteJson('/api/v1/auth/tokens/'.$otherToken->accessToken->id)
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('detail', 'The requested resource was not found.');

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }
}
