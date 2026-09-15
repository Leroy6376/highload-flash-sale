<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenResource extends JsonResource
{
    /** @return array<string, int|string|null> */
    public function toArray(Request $request): array
    {
        /** @var PersonalAccessToken $token */
        $token = $this->resource;

        return [
            'id' => $token->id,
            'name' => $token->name,
            'last_used_at' => $token->last_used_at?->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
            'created_at' => $token->created_at?->toISOString(),
        ];
    }
}
