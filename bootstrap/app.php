<?php

declare(strict_types=1);

use App\Http\Middleware\AddRequestLogContext;
use App\Http\Responses\ProblemDetailsResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', AddRequestLogContext::class);
        $middleware->appendToGroup('web', AddRequestLogContext::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                if (! is_string($field) || ! is_array($messages)) {
                    continue;
                }

                $pointer = '#/'.str_replace(['~', '.'], ['~0', '/'], $field);
                foreach ($messages as $message) {
                    if (is_string($message)) {
                        $errors[] = ['detail' => $message, 'pointer' => $pointer];
                    }
                }
            }

            return ProblemDetailsResponse::make($request, 422, 'The request contains invalid fields.', $errors);
        });
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return $request->is('api/*') ? ProblemDetailsResponse::make($request, 401, 'Authentication failed.') : null;
        });
        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            return $request->is('api/*') ? ProblemDetailsResponse::make($request, 403, 'You are not allowed to perform this action.') : null;
        });
        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            return $request->is('api/*') ? ProblemDetailsResponse::make($request, 404, 'The requested resource was not found.') : null;
        });
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            return $request->is('api/*') ? ProblemDetailsResponse::make($request, 404, 'The requested resource was not found.') : null;
        });
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            /** @var array<string, mixed> $headers */
            $headers = $exception->getHeaders();

            return $request->is('api/*') ? ProblemDetailsResponse::make($request, 429, 'Too many requests. Please try again later.', headers: $headers) : null;
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            /** @var array<string, mixed> $headers */
            $headers = $exception->getHeaders();

            return $request->is('api/*') ? ProblemDetailsResponse::make($request, $exception->getStatusCode(), 'The request could not be completed.', headers: $headers) : null;
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            return $request->is('api/*') ? ProblemDetailsResponse::make($request, 500, 'An unexpected error occurred.') : null;
        });
    })->create();
