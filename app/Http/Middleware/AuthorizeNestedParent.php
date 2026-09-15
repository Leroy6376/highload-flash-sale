<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthorizeNestedParent
{
    /**
     * @param Closure(Request): mixed $next
     */
    public function handle(Request $request, Closure $next, string $ability, string ...$parameters): mixed
    {
        foreach ($parameters as $parameter) {
            $parent = $request->route($parameter);

            if (! $parent instanceof Model) {
                continue;
            }

            Gate::authorize($ability, $parent);

            return $next($request);
        }

        throw new NotFoundHttpException();
    }
}
