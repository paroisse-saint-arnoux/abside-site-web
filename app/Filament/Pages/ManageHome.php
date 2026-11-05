<?php

namespace App\Filament\Pages;

use App\Filament\Forms\ImageUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

            Section::make('À ne pas manquer')
                ->description('Quatre événements à venir : d\'abord ceux cochés « Mettre en avant » (date de fin la plus proche en premier), complétés par les prochains événements. Rien à régler ici : la case est sur chaque événement.')
                ->schema([]),

            Section::make('Bandeau de nuit (facultatif)')
                ->description('Grande image en fond et texte façon affiche, pour un temps fort comme la Nuit des églises.')
                ->schema([
                    Toggle::make('home.highlight.enabled')->label('Afficher le bandeau'),
                    TextInput::make('home.highlight.title')->label('Titre'),
                    TextInput::make('home.highlight.label')->label('Étiquette')->helperText('Ex. « 26 et 27 juin 2027 ».'),
                    Textarea::make('home.highlight.text')->label('Texte')->rows(3),
                    TextInput::make('home.highlight.url')->label('Lien')->helperText('Un événement (/agenda/…) ou une page de mot clé (/mot-cle/…).'),
                    TextInput::make('home.highlight.button')->label('Texte du bouton')->placeholder('Découvrir'),
                    ImageUpload::make('home.highlight.image', 'accueil')->label('Image de fond'),
                    TextInput::make('home.highlight.image_alt')->label('Description de l\'image (accessibilité)'),
                ])->columns(2),

            Section::make('Qui sommes-nous')->schema([
                TextInput::make('home.about.title')->label('Titre')->required(),
                MarkdownEditor::make('home.about.text')->label('Texte'),
                ImageUpload::make('home.about.image', 'accueil')->label('Image'),
                TextInput::make('home.about.image_alt')->label('Description de l\'image (accessibilité)'),
            ]),
        ];
    }
}
