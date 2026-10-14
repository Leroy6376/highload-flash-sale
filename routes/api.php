<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogImageController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\TicketTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:api-registration');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:api-login');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::delete('logout', [AuthController::class, 'logout']);
        Route::get('tokens', [AuthController::class, 'tokens']);
        Route::delete('tokens/{token}', [AuthController::class, 'destroyToken']);
    });
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'store']);
    Route::patch('cart/items/{cartItem}', [CartController::class, 'update']);
    Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy']);
    Route::post('cart/checkout', [CartController::class, 'checkout']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/cancellations', [OrderController::class, 'cancel']);
    Route::post('orders/{order}/payment-confirmations', [OrderController::class, 'confirmPayment']);
});

Route::prefix('v1/catalog')->scopeBindings()->group(function (): void {
    Route::apiResource('events', EventController::class)->only(['index', 'show'])
        ->parameters(['events' => 'event'])
        ->scoped(['event' => 'slug']);
    Route::apiResource('events.ticket-types', TicketTypeController::class)->only(['index', 'show'])
        ->parameters(['events' => 'event', 'ticket-types' => 'ticketType'])
        ->scoped(['event' => 'slug', 'ticketType' => 'slug']);
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('events', EventController::class)->only(['store', 'update', 'destroy'])
            ->parameters(['events' => 'event'])
            ->scoped(['event' => 'slug']);
        Route::apiResource('events.ticket-types', TicketTypeController::class)->only(['store', 'update', 'destroy'])
            ->parameters(['events' => 'event', 'ticket-types' => 'ticketType'])
            ->scoped(['event' => 'slug', 'ticketType' => 'slug']);
        Route::middleware('authorize-nested-parent:update,ticketType,event')->group(function (): void {
            Route::post('events/{event:slug}/images', [CatalogImageController::class, 'store']);
            Route::patch('events/{event:slug}/images/{image}', [CatalogImageController::class, 'update']);
            Route::delete('events/{event:slug}/images/{image}', [CatalogImageController::class, 'destroy']);
            Route::post('events/{event:slug}/ticket-types/{ticketType:slug}/images', [CatalogImageController::class, 'store']);
            Route::patch('events/{event:slug}/ticket-types/{ticketType:slug}/images/{image}', [CatalogImageController::class, 'update']);
            Route::delete('events/{event:slug}/ticket-types/{ticketType:slug}/images/{image}', [CatalogImageController::class, 'destroy']);
        });
    });
});
