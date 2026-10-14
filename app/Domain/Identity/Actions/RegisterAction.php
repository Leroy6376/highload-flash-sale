<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Data\IssuedAccessToken;
use App\Domain\Identity\Data\RegisterData;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterAction
{
    public function handle(RegisterData $data): IssuedAccessToken
    {
        return DB::transaction(function () use ($data): IssuedAccessToken {
            $user = User::create([
                'email' => $data->email,
                'name' => $data->name,
                'password' => $data->password,
            ]);
            $user->assignRole(UserRole::Customer->value);

            $expiresAt = now()->addMinutes(config()->integer('sanctum.expiration'))->toImmutable();
            $accessToken = $user->createToken($data->deviceName ?? 'API token', ['*'], $expiresAt);

            return new IssuedAccessToken($user, $accessToken->plainTextToken, $expiresAt);
        });
    }
}
