<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Identity\Data\LoginData;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function loginData(): LoginData
    {
        /** @var array{email: string, password: string, device_name?: string} $validated */
        $validated = $this->validated();

        return new LoginData(
            email: $validated['email'],
            password: $validated['password'],
            deviceName: $validated['device_name'] ?? null,
        );
    }
}
