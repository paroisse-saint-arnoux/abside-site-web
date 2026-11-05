<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ManageSite extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Réglages du site';

    protected static ?string $title = 'Réglages du site';

    protected static ?string $slug = 'reglages';

    protected static ?int $navigationSort = 4;

    protected array $settingKeys = ['site', 'pages', 'legal'];

    protected function formComponents(): array
    {
        return [
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('Général')->icon(Heroicon::OutlinedGlobeAlt)->schema([
                    Textarea::make('site.description')
                        ->label('Description pour les moteurs de recherche')
                        ->helperText('Utilisée par Google et lors des partages des pages sans description propre.')
                        ->rows(3)->maxLength(300),
                    TextInput::make('site.legal_name')->label('Nom de l\'association (pied de page)'),
                    TextInput::make('site.address')->label('Adresse'),
                    TextInput::make('site.email')->label('E-mail de contact')->email()
                        ->helperText('Affiché sur le site et sur chaque événement.'),
                    TextInput::make('site.phone')->label('Téléphone')->tel(),
                ]),

                Tab::make('Réseaux & newsletter')->icon(Heroicon::OutlinedEnvelope)->schema([
                    TextInput::make('site.facebook_url')->label('Page Facebook')->url(),
                    TextInput::make('site.instagram_url')->label('Compte Instagram')->url(),
                    TextInput::make('site.newsletter_title')->label('Titre du bloc newsletter'),
                    Textarea::make('site.newsletter_text')->label('Texte du bloc newsletter')->rows(2),
                ]),

                Tab::make('Pages')->icon(Heroicon::OutlinedDocumentText)->schema([
                    Section::make('Qui sommes-nous')->schema([
                        TextInput::make('pages.about.title')->label('Titre')->required(),
                        Textarea::make('pages.about.intro')->label('Chapô')->rows(2),
                        MarkdownEditor::make('pages.about.body')->label('Texte'),
                    ])->collapsible(),
                    Section::make('Soutenir l\'association')->schema([
                        TextInput::make('pages.support.title')->label('Titre')->required(),
                        Textarea::make('pages.support.intro')->label('Chapô')->rows(2),
                        MarkdownEditor::make('pages.support.body')->label('Texte'),
                    ])->collapsible(),
                    Section::make('Presse (page Contact)')->schema([
                        TextInput::make('pages.press.title')->label('Titre'),
                        MarkdownEditor::make('pages.press.body')->label('Texte')
                            ->helperText('Contact presse, dossier de presse (lien vers un PDF)…'),
                    ])->collapsible(),
                ]),

                Tab::make('Pages légales')->icon(Heroicon::OutlinedScale)->schema([
                    MarkdownEditor::make('legal.mentions')->label('Mentions légales'),
                    MarkdownEditor::make('legal.privacy')->label('Politique de confidentialité'),
                ]),
            ])->columnSpanFull(),
        ];
    }
}
