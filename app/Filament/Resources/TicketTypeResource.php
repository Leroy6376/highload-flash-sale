<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Shared\Images\Enums\ImageCollection;
use App\Filament\Resources\TicketTypeResource\Pages;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketTypeResource extends Resource
{
    #[\Override]
    protected static ?string $model = TicketType::class;

    #[\Override]
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('event_id')->relationship('event', 'title')->searchable()->required(),
            TextInput::make('slug')->required(),
            TextInput::make('name')->required(),
            Textarea::make('description'),
            TextInput::make('price_amount')->numeric()->minValue(0)->required(),
            Select::make('currency')->options(collect(Currency::cases())->mapWithKeys(fn (Currency $currency) => [$currency->value => $currency->value]))->required(),
            TextInput::make('capacity')->numeric()->minValue(1)->required(),
            TextInput::make('sales_limit_per_user')->numeric()->minValue(1),
            DateTimePicker::make('sales_starts_at'),
            DateTimePicker::make('sales_ends_at'),
            Select::make('status')->options(collect(TicketTypeStatus::cases())->mapWithKeys(fn (TicketTypeStatus $status) => [$status->value => $status->value]))->required(),
            Repeater::make('images')->relationship()->orderColumn('sort_order')->schema([
                Select::make('collection')->options(collect(ImageCollection::cases())->mapWithKeys(fn (ImageCollection $collection) => [$collection->value => $collection->value]))->required(),
                FileUpload::make('path')->disk('public')->directory('catalog/ticket-types')->image()->required(),
                TextInput::make('alt_text'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        /** @var list<int> $paginationPageOptions */
        $paginationPageOptions = config()->array('catalog.pagination.page_options');

        return $table->columns([
            TextColumn::make('event.title')->searchable()->sortable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('price_amount')->numeric(),
            TextColumn::make('status')->badge(),
        ])->filters([
            SelectFilter::make('status')->options(collect(TicketTypeStatus::cases())->mapWithKeys(fn (TicketTypeStatus $status) => [$status->value => $status->value])),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->paginationPageOptions($paginationPageOptions)
            ->defaultPaginationPageOption(config()->integer('catalog.pagination.default_per_page'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTicketTypes::route('/'),
            'create' => Pages\CreateTicketType::route('/create'),
            'edit' => Pages\EditTicketType::route('/{record}/edit'),
        ];
    }
}
