<?php

namespace App\Filament\Pages;

use App\Filament\Forms\ImageUpload;
use App\Enums\EventType;
use App\Support\Agenda;
use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Filament\Support\Icons\Heroicon;

class ManageHome extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Page d\'accueil';

    protected static ?string $title = 'Page d\'accueil';

    protected static ?string $slug = 'accueil';

    protected static ?int $navigationSort = 3;

    protected array $settingKeys = ['home'];

    /** Couleurs des blocs : celles des six types d'événement (texte blanc dessus). */
    public static function colorOptions(): array
    {
        return collect(EventType::cases())->mapWithKeys(fn (EventType $t) => [$t->color() => '<span style="display:inline-flex;align-items:center;gap:8px">'
            .'<span style="width:16px;height:16px;border-radius:4px;background:'.$t->color().'"></span>'.e($t->label()).'</span>'])->all();
    }

    /**
     * Lien d'un bloc : une adresse complète du site (copiée depuis le navigateur) est ramenée à son chemin ;
     * pour l'agenda, seuls les filtres de recherche sont gardés (pas la pagination ni le reste).
     */
    public static function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url);
        $host = $parts['host'] ?? null;
        $path = '/'.ltrim($parts['path'] ?? '', '/');
        $ours = $host === null || in_array($host, [parse_url(config('app.url'), PHP_URL_HOST), config('app.public_host'), 'www.'.config('app.public_host')], true)
            || str_starts_with($path, '/agenda') || str_starts_with($path, '/mot-cle/');
        if (! $ours) {
            return $url; // lien vers un autre site : gardé tel quel
        }

        parse_str($parts['query'] ?? '', $query);
        if (rtrim($path, '/') === '/agenda') {
            $query = array_filter(\Illuminate\Support\Arr::only($query, [...Agenda::FILTERS, 'passes']), fn ($v) => $v !== '' && $v !== null);

            return '/agenda'.($query ? '?'.http_build_query($query) : '');
        }

        return rtrim($path, '/') ?: '/';
    }

    /** Aperçu des quatre blocs, comme sur la page d'accueil (styles en ligne : l'admin n'a pas la feuille du site). */
    public static function doorsPreview(array $doors): HtmlString
    {
        $html = collect($doors)->map(fn ($d) => '<div style="min-height:210px;padding:70px 16px 18px;border-radius:90px 90px 0 0;color:#fff;display:flex;flex-direction:column;justify-content:flex-end;gap:6px;background:'
            .e($d['color'] ?? '#152E47').'"><div style="font-family:Marcellus,Georgia,serif;font-size:1.35rem;line-height:1.2">'.e($d['title'] ?? '').'</div>'
            .(filled($d['subtitle'] ?? null) ? '<div style="font-size:.85rem;line-height:1.45;opacity:.95">'.e($d['subtitle']).'</div>' : '')
            .'<div style="font-size:.7rem;opacity:.7;word-break:break-all">'.e($d['url'] ?? '').'</div></div>')->implode('');

        return new HtmlString('<div style="background:#FDF8EC;border-radius:12px;padding:18px">'
            .'<div style="font-size:.8rem;font-weight:600;color:#4D5866;margin-bottom:12px">Aperçu</div>'
            .'<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">'.$html.'</div></div>');
    }

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
                ->description('Chaque bloc mène à l\'agenda filtré (type ou mot clé) ou à une page de mot clé. Vous pouvez coller l\'adresse complète d\'une recherche faite sur le site.')
                ->schema([
                    Grid::make(['default' => 1, 'lg' => 2])->schema([
                        Repeater::make('home.doors')->hiddenLabel()
                            ->schema([
                                TextInput::make('title')->label('Titre')->required()->live(onBlur: true),
                                TextInput::make('subtitle')->label('Sous-titre')->live(onBlur: true),
                                Select::make('color')->label('Couleur')->required()->native(false)->allowHtml()->live()
                                    ->options(self::colorOptions()),
                                TextInput::make('url')->label('Lien')->required()->live(onBlur: true)
                                    ->placeholder('/agenda?type=theatre')
                                    ->helperText('Ex. /agenda?type=theatre, /mot-cle/nuit-des-eglises, ou l\'adresse complète copiée depuis le navigateur.')
                                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('url', self::normalizeUrl($state)))
                                    ->dehydrateStateUsing(fn (?string $state) => self::normalizeUrl($state))
                                    ->suffixAction(Action::make('testUrl')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->tooltip('Tester le lien')
                                        ->url(fn (?string $state) => filled($state) ? url(self::normalizeUrl($state)) : null)->openUrlInNewTab()),
                            ])
                            ->itemLabel(fn (array $state) => $state['title'] ?? null)
                            ->collapsible()->minItems(4)->maxItems(4)
                            ->reorderableWithDragAndDrop()->addable(false)->deletable(false),
                        Text::make(fn (Get $get) => self::doorsPreview($get('home.doors') ?? []))
                            ->extraAttributes(['style' => 'position: sticky; top: 5rem; align-self: start']),
                    ]),
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
