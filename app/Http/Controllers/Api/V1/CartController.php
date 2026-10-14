<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Actions\AddCartItemAction;
use App\Domain\Orders\Actions\CheckoutCartAction;
use App\Domain\Orders\Actions\GetCurrentCartAction;
use App\Domain\Orders\Actions\RemoveCartItemAction;
use App\Domain\Orders\Actions\UpdateCartItemAction;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\CartItem;
use App\Http\Controllers\Controller;
use App\Http\Documentation\ProblemDetailsExamples;
use App\Http\Requests\Api\V1\CartItemQuantityRequest;
use App\Http\Requests\Api\V1\CartItemRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Resources\Api\V1\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Knuckles\Scribe\Attributes\Subgroup;
use Knuckles\Scribe\Attributes\UrlParam;

#[Authenticated]
#[Group('Orders')]
#[Response(content: ProblemDetailsExamples::Unauthorized, status: 401, description: 'Authentication failed.')]
#[Subgroup('Cart')]
class CartController extends Controller
{
    #[Endpoint('Текущая корзина', 'Возвращает активную корзину пользователя или `data: null`, если её нет.')]
    #[ResponseFromApiResource(CartResource::class, Cart::class, with: ['items'])]
    public function show(Request $request, GetCurrentCartAction $getCurrentCart): JsonResponse|CartResource
    {
        /** @var User $user */
        $user = $request->user();
        $cart = $getCurrentCart->handle($user);

        if ($cart === null) {
            return response()->json(['data' => null]);
        }

        return CartResource::make($cart);
    }

    #[BodyParam('ticket_type_id', 'string', 'UUID типа билета.', example: '00000000-0000-0000-0000-000000000001')]
    #[BodyParam('quantity', 'integer', 'Количество билетов, не менее одного.', example: 2)]
    #[Endpoint('Добавить билет', 'Добавляет тип билета в активную корзину или увеличивает его количество. Резерв действует 15 минут после последнего изменения.')]
    #[Response(content: ProblemDetailsExamples::Conflict, status: 409, description: 'Ticket cannot be reserved.')]
    #[Response(content: ProblemDetailsExamples::Validation, status: 422, description: 'Validation failed.')]
    #[ResponseFromApiResource(CartResource::class, Cart::class, with: ['items'])]
    public function store(CartItemRequest $request, AddCartItemAction $addCartItem): CartResource
    {
        /** @var User $user */
        $user = $request->user();

        return CartResource::make($addCartItem->handle($user, $request->cartItemData()));
    }

    #[UrlParam('cartItem_id', 'string', 'UUID позиции корзины.', example: '00000000-0000-0000-0000-000000000001')]
    #[BodyParam('quantity', 'integer', 'Новое количество билетов, не менее одного.', example: 3)]
    #[Endpoint('Изменить количество', 'Изменяет количество билетов в активной корзине.')]
    #[Response(content: ProblemDetailsExamples::Conflict, status: 409, description: 'Cart item is no longer active or ticket cannot be reserved.')]
    #[Response(content: ProblemDetailsExamples::NotFound, status: 404, description: 'Cart item not found.')]
    #[Response(content: ProblemDetailsExamples::Validation, status: 422, description: 'Validation failed.')]
    #[ResponseFromApiResource(CartResource::class, Cart::class, with: ['items'])]
    public function update(CartItemQuantityRequest $request, CartItem $cartItem, UpdateCartItemAction $updateCartItem): CartResource
    {
        /** @var User $user */
        $user = $request->user();

        return CartResource::make($updateCartItem->handle($user, $cartItem, $request->integer('quantity')));
    }

    #[UrlParam('cartItem_id', 'string', 'UUID позиции корзины.', example: '00000000-0000-0000-0000-000000000001')]
    #[Endpoint('Удалить билет', 'Удаляет позицию из активной корзины.')]
    #[Response(content: ProblemDetailsExamples::Conflict, status: 409, description: 'Cart item is no longer active.')]
    #[Response(content: ProblemDetailsExamples::NotFound, status: 404, description: 'Cart item not found.')]
    #[Response(status: 204, description: 'Cart item removed.')]
    public function destroy(Request $request, CartItem $cartItem, RemoveCartItemAction $removeCartItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $removeCartItem->handle($user, $cartItem);

        return response()->json(status: 204);
    }

    #[Endpoint('Оформить корзину', 'Создаёт заказ в статусе `pending_payment` и переводит корзину в `checked_out`. Резерв заказа действует 15 минут.')]
    #[Response(content: ProblemDetailsExamples::Conflict, status: 409, description: 'Cart cannot be checked out.')]
    #[ResponseFromApiResource(OrderResource::class, \App\Domain\Orders\Models\Order::class, status: 201, with: ['items'], description: 'Order created.')]
    public function checkout(Request $request, CheckoutCartAction $checkoutCart): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return OrderResource::make($checkoutCart->handle($user))->response()->setStatusCode(201);
    }
}
