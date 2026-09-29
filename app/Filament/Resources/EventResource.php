<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Catalog\Actions\DeleteEventAction;
use App\Domain\Catalog\Actions\EventAdminQueryAction;
use App\Domain\Catalog\Data\EventData;
use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Models\Event;
use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers\ImagesRelationManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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
            DeleteAction::make()->using(function (Model $record, DeleteEventAction $deleteEvent): bool {
                assert($record instanceof Event);
                $deleteEvent->handle($record);

                return true;
            }),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make()->using(function (Collection $records, DeleteEventAction $deleteEvent): void {
                    foreach ($records as $record) {
                        assert($record instanceof Event);
                        $deleteEvent->handle($record);
                    }
                }),
            ]),
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

    public static function getRelations(): array
    {
        return [ImagesRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @phpstan-ignore return.type (Filament fixes the parent builder generic to Model.) */
        return resolve(EventAdminQueryAction::class)->handle();
    }

    /** @param array<string, mixed> $data */
    public static function eventData(array $data): EventData
    {
        return new EventData(
            slug: self::requiredString($data, 'slug'),
            title: self::requiredString($data, 'title'),
            shortDescription: self::nullableString($data, 'short_description'),
            description: self::nullableString($data, 'description'),
            timezone: self::requiredString($data, 'timezone'),
            startsAt: self::requiredString($data, 'starts_at'),
            endsAt: self::nullableString($data, 'ends_at'),
            salesStartsAt: self::nullableString($data, 'sales_starts_at'),
            salesEndsAt: self::nullableString($data, 'sales_ends_at'),
            status: self::eventStatus($data),
        );
    }

    /** @param array<string, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw new InvalidArgumentException("The {$key} field must be a string.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value !== null && ! is_string($value)) {
            throw new InvalidArgumentException("The {$key} field must be a string or null.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private static function eventStatus(array $data): EventStatus
    {
        $value = $data['status'] ?? null;

        if ($value instanceof EventStatus) {
            return $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('The status field must be a valid event status.');
        }

        return EventStatus::from($value);
    }
}
