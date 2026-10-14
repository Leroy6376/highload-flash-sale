<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\GetCurrentUserAction;
use App\Domain\Identity\Actions\ListPersonalAccessTokensAction;
use App\Domain\Identity\Actions\LoginAction;
use App\Domain\Identity\Actions\LogoutAction;
use App\Domain\Identity\Actions\RegisterAction;
use App\Domain\Identity\Actions\RevokePersonalAccessTokenAction;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Documentation\AuthenticationResponseExamples;
use App\Http\Documentation\ProblemDetailsExamples;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\PersonalAccessTokenResource;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\Subgroup;
use Knuckles\Scribe\Attributes\Unauthenticated;

#[Group('Authentication')]
class AuthController extends Controller
{
    #[BodyParam('name', 'string', 'Имя покупателя.', example: 'Иван Петров')]
    #[BodyParam('email', 'string', 'Уникальный email покупателя.', example: 'ivan.petrov@example.test')]
    #[BodyParam('password', 'string', 'Пароль длиной не менее 8 символов.', example: 'secret-password')]
    #[BodyParam('password_confirmation', 'string', 'Повтор пароля.', example: 'secret-password')]
    #[BodyParam('device_name', 'string', 'Название устройства для токена.', required: false, example: 'iPhone 17')]
    #[Endpoint('Регистрация покупателя', 'Создаёт customer-пользователя и возвращает Bearer-токен.')]
    #[Response(content: AuthenticationResponseExamples::Registration, status: 201, description: 'Customer registered.')]
    #[Response(content: ProblemDetailsExamples::TooManyRequests, status: 429, description: 'Registration rate limit exceeded.')]
    #[Response(content: ProblemDetailsExamples::Validation, status: 422, description: 'Validation failed.')]
    #[Subgroup('Registration')]
    #[Unauthenticated]
    public function register(RegisterRequest $request, RegisterAction $register): JsonResponse
    {
        $accessToken = $register->handle($request->registerData());

        return response()->json([
            'token' => $accessToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $accessToken->expiresAt->toISOString(),
            'user' => UserResource::make($accessToken->user)->resolve(),
        ], 201);
    }

    #[Endpoint('Вход', 'Возвращает Bearer-токен для существующего пользователя.')]
    #[Response(content: ProblemDetailsExamples::Unauthorized, status: 401, description: 'Authentication failed.')]
    #[Response(content: ProblemDetailsExamples::Validation, status: 422, description: 'Validation failed.')]
    #[Subgroup('Session')]
    #[Unauthenticated]
    public function login(LoginRequest $request, LoginAction $login): JsonResponse
    {
        $accessToken = $login->handle($request->loginData());

        return response()->json([
            'token' => $accessToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $accessToken->expiresAt->toISOString(),
            'user' => UserResource::make($accessToken->user)->resolve(),
        ]);
    }

    #[Subgroup('Current user')]
    #[Authenticated]
    #[Response(content: ProblemDetailsExamples::Unauthorized, status: 401, description: 'Authentication failed.')]
    public function me(Request $request, GetCurrentUserAction $getCurrentUser): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['user' => UserResource::make($getCurrentUser->handle($user))->resolve()]);
    }

    #[Subgroup('Session')]
    #[Authenticated]
    #[Response(content: ProblemDetailsExamples::Unauthorized, status: 401, description: 'Authentication failed.')]
    public function logout(Request $request, LogoutAction $logout): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $logout->handle($user);

        return response()->json(status: 204);
    }

    #[Subgroup('Access tokens')]
    #[Authenticated]
    #[Response(content: ProblemDetailsExamples::Unauthorized, status: 401, description: 'Authentication failed.')]
    public function tokens(Request $request, ListPersonalAccessTokensAction $listTokens): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => PersonalAccessTokenResource::collection(
                $listTokens->handle($user),
            )->resolve(),
        ]);
    }

    #[Response(
        content: ProblemDetailsExamples::NotFound,
        status: 404,
        description: 'Token not found.',
    )]
    #[Subgroup('Access tokens')]
    #[Authenticated]
    #[Response(content: ProblemDetailsExamples::Unauthorized, status: 401, description: 'Authentication failed.')]
    public function destroyToken(
        Request $request,
        string $token,
        RevokePersonalAccessTokenAction $revokeToken,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $revokeToken->handle($user, $token);

        return response()->json(status: 204);
    }
}
