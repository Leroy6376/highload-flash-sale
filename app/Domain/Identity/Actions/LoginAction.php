<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Data\IssuedAccessToken;
use App\Domain\Identity\Data\LoginData;
use App\Domain\Identity\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Hashing\Hasher;

class LoginAction
{
    public function __construct(private readonly Hasher $hasher) {}

    public function handle(LoginData $data): IssuedAccessToken
    {
        $user = User::query()->where('email', $data->email)->first();
        if ($user === null || ! $this->hasher->check($data->password, $user->password)) {
            throw new AuthenticationException();
        }

        $expiresAt = now()->addMinutes(config('sanctum.expiration'))->toImmutable();
        $accessToken = $user->createToken($data->deviceName ?? 'API token', ['*'], $expiresAt);

        return new IssuedAccessToken($user, $accessToken->plainTextToken, $expiresAt);
    }
}
