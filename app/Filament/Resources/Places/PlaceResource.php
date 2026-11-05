<?php

namespace App\Filament\Resources\Places;

use App\Filament\Resources\Places\Pages\ManagePlaces;
use App\Models\Place;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $modelLabel = 'lieu';

    protected static ?string $pluralModelLabel = 'lieux';

    protected static ?string $slug = 'lieux';

    protected static ?int $navigationSort = 2;

    /** Champs d'un lieu, réutilisés pour la création rapide depuis un événement. */
    public static function formFields(): array
    {
        return [
            TextInput::make('name')->label('Nom affiché')->placeholder('Église Saint-Étienne')->required()->maxLength(190),
            TextInput::make('address')->label('Adresse')->placeholder('32 rue Vernier')->maxLength(190),
            Grid::make(2)->schema([
                TextInput::make('postal_code')->label('Code postal')->maxLength(10),
                TextInput::make('city')->label('Commune')->required()->maxLength(120),
                TextInput::make('latitude')->label('Latitude')->numeric()->minValue(-90)->maxValue(90),
                TextInput::make('longitude')->label('Longitude')->numeric()->minValue(-180)->maxValue(180),
            ]),
            \Filament\Schemas\Components\Text::make('Coordonnées GPS : dans Google Maps, clic droit sur le lieu, puis cliquez sur les chiffres pour les copier (latitude, longitude). Laissées vides, elles sont recherchées à partir de l\'adresse.'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields())->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('city')->label('Commune')->searchable()->sortable(),
                TextColumn::make('address')->label('Adresse')->toggleable(),
                TextColumn::make('events_count')->label('Événements')->counts('events')->sortable(),
                TextColumn::make('latitude')->label('GPS')->formatStateUsing(fn (Place $record) => $record->hasCoordinates() ? 'Oui' : 'Non')
                    ->badge()->color(fn (Place $record) => $record->hasCoordinates() ? 'success' : 'warning'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePlaces::route('/')];
    }
}
