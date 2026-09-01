<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, int|string|list<string>> */
    public function toArray(Request $request): array
    {
        /** @var list<string> $roles */
        $roles = [];
        foreach ($this->roles as $role) {
            $name = $role->getAttribute('name');
            if (is_string($name)) {
                $roles[] = $name;
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles,
        ];
    }
}
