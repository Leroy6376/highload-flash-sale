<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Catalog\Contracts\PendingAllocationInvalidator;
use App\Domain\Catalog\Contracts\TicketAllocationReader;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Services\TicketAllocationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TicketAllocationService::class);
        $this->app->alias(TicketAllocationService::class, PendingAllocationInvalidator::class);
        $this->app->alias(TicketAllocationService::class, TicketAllocationReader::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user): ?bool => $user->hasRole(UserRole::SuperAdmin->value) ? true : null);
        RateLimiter::for('api-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::lower($request->string('email')->toString()).'|'.$request->ip());
        });
        RateLimiter::for('api-registration', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::lower($request->string('email')->toString()).'|'.$request->ip());
        });
        Relation::enforceMorphMap([
            'event' => Event::class,
            'ticket_type' => TicketType::class,
            'user' => User::class,
        ]);
    }
}
