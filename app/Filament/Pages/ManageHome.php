<?php

namespace App\Filament\Pages;

use App\Filament\Forms\ImageUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class ManageHome extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Page d\'accueil';

    protected static ?string $title = 'Page d\'accueil';

    protected static ?string $slug = 'accueil';

    protected static ?int $navigationSort = 3;

    protected array $settingKeys = ['home'];

    /** Couleurs de la charte proposées pour les blocs (texte blanc dessus). */
    public const COLORS = ['#1F4E8C', '#B4492C', '#2F5D50', '#6A3D7C', '#7A5A12', '#152E47'];

    protected function formComponents(): array
    {
        return [
            Section::make('Introduction')->schema([
                TextInput::make('home.hero.title')->label('Titre')->required(),
                Textarea::make('home.hero.text')->label('Texte')->rows(3)
                    ->helperText('Sert aussi de description de la page d\'accueil pour Google.'),
                ImageUpload::make('home.hero.image', 'accueil')->label('Image (en voûte, à droite)'),
                TextInput::make('home.hero.image_alt')->label('Description de l\'image (accessibilité)'),
            ]),

            Section::make('Les quatre blocs')
                ->description('Chaque bloc mène à l\'agenda filtré (type ou mot clé) ou à une page de mot clé.')
                ->schema([
                    Repeater::make('home.doors')->hiddenLabel()
                        ->schema([
                            TextInput::make('title')->label('Titre')->required(),
                            TextInput::make('subtitle')->label('Sous-titre'),
                            ColorPicker::make('color')->label('Couleur')->required()
                                ->helperText('Charte : '.implode(' · ', self::COLORS)),
                            TextInput::make('url')->label('Lien')->required()
                                ->helperText('Ex. /agenda?type=theatre ou /mot-cle/nuit-des-eglises'),
                        ])
                        ->columns(2)->minItems(4)->maxItems(4)
                        ->reorderableWithDragAndDrop()->addable(false)->deletable(false),
                ]),

            Section::make('Qui sommes-nous')->schema([
                TextInput::make('home.about.title')->label('Titre')->required(),
                MarkdownEditor::make('home.about.text')->label('Texte'),
                ImageUpload::make('home.about.image', 'accueil')->label('Image'),
                TextInput::make('home.about.image_alt')->label('Description de l\'image (accessibilité)'),
            ]),
        ];
    }
}
