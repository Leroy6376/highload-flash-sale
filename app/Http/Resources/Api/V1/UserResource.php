<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string, int|string|list<string>> */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        /** @var list<string> $roles */
        $roles = [];
        foreach ($user->roles as $role) {
            $name = $role->getAttribute('name');
            if (is_string($name)) {
                $roles[] = $name;
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roles,
        ];
    }
}
