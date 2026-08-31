<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestLogContext
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::ulid();

        Context::add([
            'request_id' => $requestId,
            'request_method' => $request->method(),
            'request_path' => '/'.$request->path(),
        ]);

        $userId = $request->user()?->getAuthIdentifier();

        if ($userId !== null) {
            Context::add('user_id', $userId);
        }

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
