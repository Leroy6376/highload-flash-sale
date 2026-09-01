<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

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
