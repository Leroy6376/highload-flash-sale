<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Actions\CancelOrderAction;
use App\Domain\Orders\Actions\ConfirmOrderPaymentAction;
use App\Domain\Orders\Actions\ListOrdersAction;
use App\Domain\Orders\Actions\ShowOrderAction;
use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Documentation\ProblemDetailsExamples;
use App\Http\Requests\Api\V1\OrderPaginationRequest;
use App\Http\Resources\Api\V1\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Knuckles\Scribe\Attributes\Subgroup;
use Knuckles\Scribe\Attributes\UrlParam;

#[Authenticated]
#[Group('Orders')]
#[Response(content: ProblemDetailsExamples::Unauthorized, status: 401, description: 'Authentication failed.')]
#[Subgroup('Orders')]
class OrderController extends Controller
{
    #[QueryParam('page', 'integer', 'Номер страницы.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Количество заказов на странице (от 1 до 100).', required: false, example: 15)]
    #[Endpoint('Список заказов', 'Возвращает заказы текущего пользователя, от новых к старым.')]
    #[Response(content: ProblemDetailsExamples::Validation, status: 422, description: 'Validation failed.')]
    #[ResponseFromApiResource(OrderResource::class, Order::class, collection: true, paginate: 15, with: ['items'])]
    public function index(OrderPaginationRequest $request, ListOrdersAction $listOrders): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return OrderResource::collection($listOrders->handle($user, $request->perPage()));
    }

    #[UrlParam('order_id', 'string', 'UUID заказа.', example: '00000000-0000-0000-0000-000000000001')]
    #[Endpoint('Получить заказ', 'Возвращает заказ текущего пользователя вместе с его позициями.')]
    #[Response(content: ProblemDetailsExamples::NotFound, status: 404, description: 'Order not found.')]
    #[ResponseFromApiResource(OrderResource::class, Order::class, with: ['items'])]
    public function show(Request $request, Order $order, ShowOrderAction $showOrder): OrderResource
    {
        /** @var User $user */
        $user = $request->user();

        return OrderResource::make($showOrder->handle($user, $order));
    }

    #[UrlParam('order_id', 'string', 'UUID заказа.', example: '00000000-0000-0000-0000-000000000001')]
    #[Endpoint('Отменить заказ', 'Отменяет только заказ в статусе `pending_payment` с неистёкшим резервом.')]
    #[Response(content: ProblemDetailsExamples::Conflict, status: 409, description: 'Order cannot be cancelled.')]
    #[Response(content: ProblemDetailsExamples::NotFound, status: 404, description: 'Order not found.')]
    #[ResponseFromApiResource(OrderResource::class, Order::class, with: ['items'])]
    public function cancel(Request $request, Order $order, CancelOrderAction $cancelOrder): OrderResource
    {
        /** @var User $user */
        $user = $request->user();

        return OrderResource::make($cancelOrder->handle($user, $order));
    }

    #[UrlParam('order_id', 'string', 'UUID заказа.', example: '00000000-0000-0000-0000-000000000001')]
    #[Endpoint('Подтвердить оплату', 'Переводит неистёкший заказ из `pending_payment` в `paid`. Требует разрешение `orders.payment-confirm`.')]
    #[Response(content: ProblemDetailsExamples::Conflict, status: 409, description: 'Order cannot be paid.')]
    #[Response(content: ProblemDetailsExamples::Forbidden, status: 403, description: 'Missing orders.payment-confirm permission.')]
    #[Response(content: ProblemDetailsExamples::NotFound, status: 404, description: 'Order not found.')]
    #[ResponseFromApiResource(OrderResource::class, Order::class, with: ['items'])]
    public function confirmPayment(Order $order, ConfirmOrderPaymentAction $confirmOrderPayment): OrderResource
    {
        $this->authorize('confirmPayment', $order);

        return OrderResource::make($confirmOrderPayment->handle($order));
    }
}
