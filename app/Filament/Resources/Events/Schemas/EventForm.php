<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventType;
use App\Enums\Registration;
use App\Filament\Forms\ImageUpload;
use App\Filament\Resources\Places\PlaceResource;
use App\Models\Event;
use App\Support\Markdown;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                    Section::make('Événement')->schema([
                        TextInput::make('title')->label('Titre')->required()->maxLength(190),
                        Grid::make(2)->schema([
                            Select::make('type')->label('Type')->options(EventType::options())->required()->native(false)
                                ->helperText('« Événement » regroupe tout ce qui n\'entre pas dans les autres types.'),
                            Select::make('keywords')->label('Mots clés')->relationship('keywords', 'name')
                                ->multiple()->searchable()->preload()
                                ->createOptionForm([TextInput::make('name')->label('Mot clé')->required()->maxLength(80)])
                                ->helperText('Ex. « Art & Foi », « Nuit des églises ». Un mot clé peut avoir sa propre page.'),
                        ]),
                        Textarea::make('intro')->label('Chapô')->rows(3)->maxLength(400)
                            ->helperText('2 ou 3 phrases, en tête de page et lors des partages (Facebook, WhatsApp…).'),
                    ]),

                    Section::make('Description')->schema([
                        Tabs::make('editor')->tabs([
                            Tab::make('Rédaction')->icon('heroicon-o-pencil')->schema([
                                MarkdownEditor::make('body')->hiddenLabel()
                                    ->toolbarButtons([['bold', 'italic', 'link'], ['heading', 'bulletList', 'orderedList', 'blockquote'], ['attachFiles'], ['undo', 'redo']])
                                    ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('evenements/contenu')
                                    ->saveUploadedFileAttachmentUsing(fn (TemporaryUploadedFile $file) => ImageUpload::save($file, 'evenements/contenu'))
                                    ->minHeight('16rem')->live(onBlur: true),
                            ]),
                            Tab::make('Aperçu')->icon('heroicon-o-eye')->schema([
                                View::make('filament.markdown-preview')->viewData(fn (Get $get) => ['html' => Markdown::render($get('body'))]),
                            ]),
                        ]),
                    ]),

                    Section::make('Dates')->schema([
                        Radio::make('schedule_mode')->hiddenLabel()->inline()->live()->default('dates')
                            ->options(['dates' => 'Une ou plusieurs dates', 'recurring' => 'Horaires récurrents (exposition…)']),

                        Repeater::make('dates')->relationship()->hiddenLabel()
                            ->visible(fn (Get $get) => $get('schedule_mode') !== 'recurring')
                            ->schema([
                                DateTimePicker::make('starts_at')->label('Début')->required()->seconds(false)->native(false)->displayFormat('d/m/Y H:i'),
                                DateTimePicker::make('ends_at')->label('Fin (facultatif)')->seconds(false)->native(false)->displayFormat('d/m/Y H:i')->after('starts_at'),
                                TextInput::make('label')->label('Sous-titre de la séance (facultatif)')
                                    ->helperText('Ex. le thème d\'une conférence dans un cycle.')->maxLength(190),
                            ])
                            ->columns(3)->orderColumn(false)->defaultItems(1)->addActionLabel('Ajouter une date')
                            ->itemLabel(fn (array $state) => filled($state['starts_at'] ?? null) ? \Illuminate\Support\Carbon::parse($state['starts_at'])->translatedFormat('l j F Y, H:i') : null),

                        Grid::make(2)->visible(fn (Get $get) => $get('schedule_mode') === 'recurring')->schema([
                            DatePicker::make('recurrence.from')->label('Du')->required()->native(false)->displayFormat('d/m/Y'),
                            DatePicker::make('recurrence.to')->label('Au')->required()->native(false)->displayFormat('d/m/Y')->afterOrEqual('recurrence.from'),
                            CheckboxList::make('recurrence.weekdays')->label('Jours d\'ouverture')->required()->columns(4)->columnSpanFull()
                                ->options(['1' => 'Lundi', '2' => 'Mardi', '3' => 'Mercredi', '4' => 'Jeudi', '5' => 'Vendredi', '6' => 'Samedi', '7' => 'Dimanche']),
                            Repeater::make('recurrence.slots')->label('Créneaux')->required()->columnSpanFull()
                                ->helperText('Plusieurs créneaux pour une pause déjeuner : 10h–12h puis 14h–18h.')
                                ->schema([
                                    TimePicker::make('start')->label('De')->required()->seconds(false),
                                    TimePicker::make('end')->label('À')->seconds(false),
                                ])->columns(2)->defaultItems(1)->addActionLabel('Ajouter un créneau'),
                            Repeater::make('recurrence.exceptions')->label('Jours de fermeture exceptionnelle')->columnSpanFull()
                                ->simple(DatePicker::make('date')->native(false)->displayFormat('d/m/Y')->required())
                                ->defaultItems(0)->addActionLabel('Ajouter un jour de fermeture'),
                        ]),
                    ]),

                    Section::make('Caractéristiques')
                        ->description('Jusqu\'à '.Event::MAX_SPECS.' lignes « clé / valeur » propres à l\'événement : « D\'après », « Mise en scène », « Avec »… La valeur accepte le Markdown et plusieurs lignes.')
                        ->collapsible()
                        ->schema([
                            Repeater::make('specs')->hiddenLabel()
                                ->schema([
                                    TextInput::make('key')->label('Clé')->required()->maxLength(80),
                                    Textarea::make('value')->label('Valeur')->required()->rows(2),
                                ])
                                ->columns(2)->maxItems(Event::MAX_SPECS)->defaultItems(0)->reorderableWithDragAndDrop()
                                ->addActionLabel('Ajouter une caractéristique'),
                        ]),

                    Section::make('Galerie photos')
                        ->description('Grille sur ordinateur, carrousel automatique sur mobile.')
                        ->collapsible()
                        ->schema([
                            Repeater::make('gallery')->hiddenLabel()
                                ->schema([
                                    ImageUpload::make('path', 'evenements/galerie')->label('Photo')->required(),
                                    TextInput::make('alt')->label('Description de la photo')->required()->maxLength(190),
                                ])
                                ->grid(2)->reorderableWithDragAndDrop()->collapsible()->defaultItems(0)
                                ->itemLabel(fn (array $state) => $state['alt'] ?? null)->addActionLabel('Ajouter une photo'),
                        ]),

                    Section::make('Partenaires')
                        ->description('Un logo par ligne, ou une seule image contenant une bande de plusieurs logos. Glissez pour changer l\'ordre.')
                        ->collapsible()
                        ->schema([
                            Repeater::make('partners')->hiddenLabel()
                                ->schema([
                                    ImageUpload::make('path', 'evenements/partenaires', 1200)->label('Logo ou bande de logos')->required(),
                                    TextInput::make('alt')->label('Nom du ou des partenaires')->required()->maxLength(190),
                                ])
                                ->grid(2)->reorderableWithDragAndDrop()->collapsible()->defaultItems(0)
                                ->itemLabel(fn (array $state) => $state['alt'] ?? null)->addActionLabel('Ajouter un partenaire'),
                        ]),
                ]),

                Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                    Section::make('Publication')->schema([
                        Flex::make([
                            Toggle::make('is_published')
                                ->label(fn (Get $get) => $get('is_published') ? 'Publié' : 'Brouillon')
                                ->helperText(fn (Get $get) => $get('is_published') ? 'Visible sur le site.' : 'Non visible : seul le lien de prévisualisation permet de le voir.')
                                ->onColor('success')->offColor('warning')->onIcon('heroicon-m-globe-alt')->offIcon('heroicon-m-pencil')
                                ->inline()->live()->default(false),
                            Actions::make([
                                Action::make('open')
                                    ->label(fn (Event $record) => $record->is_published ? 'Voir sur le site' : 'Prévisualiser')
                                    ->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->size('sm')
                                    ->url(fn (Event $record) => $record->is_published ? $record->url() : $record->previewUrl())
                                    ->openUrlInNewTab(),
                            ])->grow(false)->visibleOn('edit'),
                        ])->verticallyAlignStart(),
                        Toggle::make('is_featured')->label('Mettre en avant sur l\'accueil')
                            ->helperText('Ligne « À ne pas manquer » (4 événements à venir).'),
                        Toggle::make('is_spotlight')->label('En exergue sur l\'agenda')
                            ->helperText('Grand bandeau en tête de l\'agenda.'),
                        TextInput::make('slug')->label('Adresse (URL)')->prefix('/agenda/')
                            ->helperText('Laisser vide pour la créer à partir du titre.')
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->validationMessages(['regex' => 'Uniquement des minuscules sans accents, des chiffres et des tirets.'])
                            ->unique(ignoreRecord: true)->maxLength(190),
                        TextInput::make('preview_link')->label('Lien de prévisualisation')
                            ->formatStateUsing(fn (?Event $record) => $record?->previewUrl())
                            ->readOnly()->copyable(copyMessage: 'Lien copié')->dehydrated(false)->visibleOn('edit'),
                    ]),

                    Section::make('Lieu')->schema([
                        Select::make('place_id')->hiddenLabel()->relationship('place', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' — '.$record->city)
                            ->searchable()->preload()
                            ->createOptionForm(PlaceResource::formFields())
                            ->editOptionForm(PlaceResource::formFields()),
                    ]),

                    Section::make('Entrée')->schema([
                        Toggle::make('is_free')->label('Participation libre')->default(true),
                        TextInput::make('price_text')->label('Tarif ou précision (facultatif)')
                            ->placeholder('Entrée chapelle + musée : 7 €')->maxLength(190),
                    ]),

                    Section::make('Inscription')->schema([
                        Select::make('registration')->hiddenLabel()->options(Registration::options())->default('optional')->required()->native(false)->live(),
                        TextInput::make('capacity')->label('Nombre de places par date')->numeric()->minValue(1)
                            ->helperText('Laisser vide si illimité. Une fois complet, le formulaire se ferme.')
                            ->visible(fn (Get $get) => $get('registration') !== 'none'),
                    ]),

                    Section::make('Images')->schema([
                        ImageUpload::make('image', 'evenements')->label('Photo mise en avant')
                            ->helperText('En fond du haut de page et dans l\'image de partage générée. Format paysage.'),
                        TextInput::make('image_alt')->label('Description de la photo')->requiredWith('image')->maxLength(190),
                        ImageUpload::make('poster', 'evenements/affiches', 1600)->label('Affiche'),
                    ]),
                ]),
            ]);
    }
}
