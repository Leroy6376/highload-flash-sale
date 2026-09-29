<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Catalog\Actions\DeleteTicketTypeAction;
use App\Domain\Catalog\Actions\TicketTypeAdminQueryAction;
use App\Domain\Catalog\Data\TicketTypeData;
use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\TicketType;
use App\Filament\Resources\TicketTypeResource\Pages;
use App\Filament\Resources\TicketTypeResource\RelationManagers\ImagesRelationManager;
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
            DeleteAction::make()->using(function (Model $record, DeleteTicketTypeAction $deleteTicketType): bool {
                assert($record instanceof TicketType);
                $deleteTicketType->handle($record);

                return true;
            }),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make()->using(function (Collection $records, DeleteTicketTypeAction $deleteTicketType): void {
                    foreach ($records as $record) {
                        assert($record instanceof TicketType);
                        $deleteTicketType->handle($record);
                    }
                }),
            ]),
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

    public static function getRelations(): array
    {
        return [ImagesRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @phpstan-ignore return.type (Filament fixes the parent builder generic to Model.) */
        return resolve(TicketTypeAdminQueryAction::class)->handle();
    }

    /** @param array<string, mixed> $data */
    public static function ticketTypeData(array $data): TicketTypeData
    {
        return new TicketTypeData(
            slug: self::requiredString($data, 'slug'),
            name: self::requiredString($data, 'name'),
            description: self::nullableString($data, 'description'),
            priceAmount: self::requiredInteger($data, 'price_amount'),
            currency: self::currency($data),
            capacity: self::requiredInteger($data, 'capacity'),
            salesLimitPerUser: self::nullableInteger($data, 'sales_limit_per_user'),
            salesStartsAt: self::nullableString($data, 'sales_starts_at'),
            salesEndsAt: self::nullableString($data, 'sales_ends_at'),
            status: self::ticketTypeStatus($data),
            eventId: self::nullableString($data, 'event_id'),
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
    private static function requiredInteger(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (! is_int($value) && ! is_numeric($value)) {
            throw new InvalidArgumentException("The {$key} field must be an integer.");
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $data */
    private static function nullableInteger(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_numeric($value)) {
            throw new InvalidArgumentException("The {$key} field must be an integer or null.");
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $data */
    private static function currency(array $data): Currency
    {
        $value = $data['currency'] ?? null;

        if ($value instanceof Currency) {
            return $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('The currency field must be a valid currency.');
        }

        return Currency::from($value);
    }

    /** @param array<string, mixed> $data */
    private static function ticketTypeStatus(array $data): TicketTypeStatus
    {
        $value = $data['status'] ?? null;

        if ($value instanceof TicketTypeStatus) {
            return $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('The status field must be a valid ticket type status.');
        }

        return TicketTypeStatus::from($value);
    }
}
