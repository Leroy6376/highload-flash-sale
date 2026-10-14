<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Orders\Actions\ConfirmOrderPaymentAction;
use App\Domain\Orders\Actions\OrderAdminQueryAction;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Filament\Resources\OrderResource\Pages;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    #[\Override]
    protected static ?string $model = Order::class;

    #[\Override]
    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('id')->copyable(),
            TextEntry::make('user.email'),
            TextEntry::make('status')->badge(),
            TextEntry::make('total_amount')->numeric(),
            TextEntry::make('currency'),
            TextEntry::make('expires_at')->dateTime(),
            TextEntry::make('paid_at')->dateTime(),
            TextEntry::make('cancelled_at')->dateTime(),
            TextEntry::make('expired_at')->dateTime(),
            RepeatableEntry::make('items')->schema([
                TextEntry::make('ticket_type_name'),
                TextEntry::make('quantity')->numeric(),
                TextEntry::make('unit_price_amount')->numeric(),
                TextEntry::make('currency'),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        /** @var list<int> $paginationPageOptions */
        $paginationPageOptions = config()->array('orders.pagination.page_options');

        return $table->columns([
            TextColumn::make('id')->copyable()->searchable(),
            TextColumn::make('user.email')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('total_amount')->numeric(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status) => [$status->value => $status->value])),
        ])->recordActions([
            ViewAction::make(),
            Action::make('confirmPayment')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::PendingPayment)
                ->action(function (Model $record, ConfirmOrderPaymentAction $confirmOrderPayment): void {
                    assert($record instanceof Order);
                    $confirmOrderPayment->handle($record);
                }),
        ])->paginationPageOptions($paginationPageOptions)
            ->defaultPaginationPageOption(config()->integer('orders.pagination.default_per_page'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @phpstan-ignore return.type (Filament fixes the parent builder generic to Model.) */
        return resolve(OrderAdminQueryAction::class)->handle();
    }
}
