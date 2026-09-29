<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Domain\Catalog\Models\Event;
use App\Domain\Shared\Images\Actions\CreateImageAction;
use App\Domain\Shared\Images\Actions\DeleteImageAction;
use App\Domain\Shared\Images\Actions\UpdateImageAction;
use App\Domain\Shared\Images\Data\CreateImageData;
use App\Domain\Shared\Images\Data\UpdateImageData;
use App\Domain\Shared\Images\Enums\ImageCollection;
use App\Domain\Shared\Images\Models\Image;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class ImagesRelationManager extends RelationManager
{
    #[\Override]
    protected static string $relationship = 'images';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('file')->storeFiles(false)->image()->required(fn (string $operation): bool => $operation === 'create'),
            Select::make('collection')->options(collect(ImageCollection::cases())->mapWithKeys(fn (ImageCollection $collection) => [$collection->value => $collection->value]))->required(),
            TextInput::make('alt_text')->maxLength(255),
            TextInput::make('sort_order')->numeric()->minValue(0)->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->columns([
                TextColumn::make('collection')->badge(),
                TextColumn::make('path')->searchable(),
                TextColumn::make('alt_text'),
                TextColumn::make('sort_order')->numeric()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->using(function (array $data, CreateImageAction $createImage): Model {
                    /** @var array<string, mixed> $data */
                    $owner = $this->getOwnerRecord();
                    assert($owner instanceof Event);

                    return $createImage->handle($owner, $this->createImageData($data));
                }),
            ])
            ->recordActions([
                EditAction::make()->using(function (Image $record, array $data, UpdateImageAction $updateImage): void {
                    /** @var array<string, mixed> $data */
                    $owner = $this->getOwnerRecord();
                    assert($owner instanceof Event);
                    $updateImage->handle($owner, $record, $this->updateImageData($data));
                }),
                DeleteAction::make()->using(function (Image $record, DeleteImageAction $deleteImage): bool {
                    $deleteImage->handle($record);

                    return true;
                }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->using(function (Collection $records, DeleteImageAction $deleteImage): void {
                        foreach ($records as $record) {
                            assert($record instanceof Image);
                            $deleteImage->handle($record);
                        }
                    }),
                ]),
            ]);
    }

    /** @param array<string, mixed> $data */
    private function createImageData(array $data): CreateImageData
    {
        $file = $data['file'] ?? null;
        assert($file instanceof UploadedFile);

        return new CreateImageData(
            file: $file,
            collection: ImageCollection::from($this->requiredString($data, 'collection')),
            altText: $this->nullableString($data, 'alt_text'),
            sortOrder: $this->nullableInteger($data, 'sort_order') ?? 0,
        );
    }

    /** @param array<string, mixed> $data */
    private function updateImageData(array $data): UpdateImageData
    {
        $file = $data['file'] ?? null;

        return new UpdateImageData(
            file: $file instanceof UploadedFile ? $file : null,
            hasCollection: array_key_exists('collection', $data),
            collection: isset($data['collection']) ? ImageCollection::from($this->requiredString($data, 'collection')) : null,
            hasAltText: array_key_exists('alt_text', $data),
            altText: $this->nullableString($data, 'alt_text'),
            hasSortOrder: array_key_exists('sort_order', $data),
            sortOrder: $this->nullableInteger($data, 'sort_order'),
        );
    }

    /** @param array<string, mixed> $data */
    private function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw new InvalidArgumentException("The {$key} field must be a string.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value !== null && ! is_string($value)) {
            throw new InvalidArgumentException("The {$key} field must be a string or null.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableInteger(array $data, string $key): ?int
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
}
