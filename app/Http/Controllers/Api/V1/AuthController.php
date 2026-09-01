<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\ListPersonalAccessTokensAction;
use App\Domain\Identity\Actions\LoginAction;
use App\Domain\Identity\Actions\LogoutAction;
use App\Domain\Identity\Actions\RevokePersonalAccessTokenAction;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Documentation\ProblemDetailsExamples;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\PersonalAccessTokenResource;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Response;

#[Response(
    content: ProblemDetailsExamples::Unauthorized,
    status: 401,
    description: 'Authentication failed.',
)]
class AuthController extends Controller
{
    #[Response(
        content: ProblemDetailsExamples::Validation,
        status: 422,
        description: 'Validation failed.',
    )]
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

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['user' => UserResource::make($user)->resolve()]);
    }

    public function logout(Request $request, LogoutAction $logout): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $logout->handle($user);

        return response()->json(status: 204);
    }

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
