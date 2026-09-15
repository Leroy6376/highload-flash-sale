<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Shared\Images\Enums\ImageCollection;
use App\Filament\Resources\EventResource\Pages;
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

class EventResource extends Resource
{
    #[\Override]
    protected static ?string $model = Event::class;

    #[\Override]
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            TextInput::make('title')->required(),
            Textarea::make('short_description'),
            Textarea::make('description')->columnSpanFull(),
            TextInput::make('timezone')->required()->default('Europe/Moscow'),
            DateTimePicker::make('starts_at')->required(),
            DateTimePicker::make('ends_at'),
            DateTimePicker::make('sales_starts_at'),
            DateTimePicker::make('sales_ends_at'),
            Select::make('status')->options(collect(EventStatus::cases())->mapWithKeys(fn (EventStatus $status) => [$status->value => $status->value]))->required(),
            Repeater::make('images')->relationship()->orderColumn('sort_order')->schema([
                Select::make('collection')->options(collect(ImageCollection::cases())->mapWithKeys(fn (ImageCollection $collection) => [$collection->value => $collection->value]))->required(),
                FileUpload::make('path')->disk('public')->directory('catalog/events')->image()->required(),
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
            TextColumn::make('title')->searchable()->sortable(),
            TextColumn::make('slug')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('starts_at')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(collect(EventStatus::cases())->mapWithKeys(fn (EventStatus $status) => [$status->value => $status->value])),
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
