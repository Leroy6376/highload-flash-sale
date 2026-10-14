<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Identity\Data\RegisterData;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'device_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'lowercase',
                Rule::unique(User::class),
            ],
            'name' => ['required', 'string', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function registerData(): RegisterData
    {
        /**
         * @var array{
         *     name: string,
         *     email: string,
         *     password: string,
         *     device_name?: string
         * } $validated
         */
        $validated = $this->validated();

        return new RegisterData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            deviceName: $validated['device_name'] ?? null,
        );
    }
}
