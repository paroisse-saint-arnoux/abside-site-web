<?php

namespace App\Filament\Resources\Keywords;

use App\Filament\Forms\ImageUpload;
use App\Filament\Resources\Keywords\Pages\ManageKeywords;
use App\Models\Keyword;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KeywordResource extends Resource
{
    protected static ?string $model = Keyword::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $modelLabel = 'mot clé';

    protected static ?string $pluralModelLabel = 'mots clés';

    protected static ?string $slug = 'mots-cles';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('Mot clé')->required()->maxLength(80),
            TextInput::make('slug')->label('Adresse')->prefix('/mot-cle/')
                ->helperText('Laisser vide pour la créer à partir du mot clé.')
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)->maxLength(80),
            Toggle::make('has_page')->label('Ce mot clé a sa propre page')->live()
                ->helperText('Sinon, les liens vers ce mot clé mènent à l\'agenda filtré.'),
            Section::make('Page du mot clé')
                ->description('Même gabarit qu\'un événement, suivi de la liste des événements de ce mot clé.')
                ->visible(fn (Get $get) => (bool) $get('has_page'))
                ->schema([
                    Textarea::make('intro')->label('Chapô')->rows(3)->maxLength(400),
                    MarkdownEditor::make('body')->label('Texte'),
                    TextInput::make('when_label')->label('Dates (texte libre)')->placeholder('26 et 27 juin 2027'),
                    TextInput::make('where_label')->label('Où (texte libre)')->placeholder('Églises des Alpes-Maritimes'),
                    ImageUpload::make('image', 'mots-cles')->label('Photo mise en avant'),
                    TextInput::make('image_alt')->label('Description de la photo')->requiredWith('image'),
                    Repeater::make('gallery')->label('Galerie')
                        ->schema([
                            ImageUpload::make('path', 'mots-cles/galerie')->label('Photo')->required(),
                            TextInput::make('alt')->label('Description')->required(),
                        ])
                        ->grid(2)->reorderableWithDragAndDrop()->collapsible()->defaultItems(0)->addActionLabel('Ajouter une photo'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Mot clé')->searchable()->sortable(),
                IconColumn::make('has_page')->label('Page')->boolean(),
                TextColumn::make('events_count')->label('Événements')->counts('events')->sortable(),
            ])
            ->recordActions([
                Action::make('voir')->label('Voir')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Keyword $record) => $record->url())->openUrlInNewTab(),
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageKeywords::route('/')];
    }
}
