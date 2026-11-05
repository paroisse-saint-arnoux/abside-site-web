<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Contacts\RelationManagers\RegistrationsRelationManager;
use App\Models\Contact;
use App\Support\Spreadsheet;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/** Carnet des personnes inscrites à au moins un événement (une fiche par e-mail). */
class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'contact';

    protected static ?string $pluralModelLabel = 'contacts';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $slug = 'contacts';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('first_name')->label('Prénom')->required()->maxLength(80),
                TextInput::make('last_name')->label('Nom')->required()->maxLength(80),
                TextInput::make('email')->label('E-mail')->email()->required()->maxLength(190)->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Téléphone')->tel()->maxLength(30),
                Text::make(fn (?Contact $record) => $record
                    ? 'Ajouté le '.$record->created_at->format('d/m/Y').($record->newsletter_at ? ' · inscrit à la newsletter le '.$record->newsletter_at->format('d/m/Y') : '')
                        .'. Supprimé automatiquement '.Contact::RETENTION_YEARS.' ans après sa dernière participation.'
                    : '')->columnSpanFull(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withStats())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('last_name')->label('Nom')->searchable(['last_name', 'first_name'])->sortable()
                    ->formatStateUsing(fn (Contact $record) => $record->fullName())->weight('bold'),
                TextColumn::make('email')->label('E-mail')->searchable()->copyable(),
                TextColumn::make('phone')->label('Téléphone')->placeholder('—')->toggleable(),
                TextColumn::make('events_count')->label('Événements')->alignCenter()->sortable(),
                TextColumn::make('last_event_at')->label('Dernier événement')->date('d/m/Y')->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label('Ajouté le')->date('d/m/Y')->sortable(),
                IconColumn::make('newsletter_at')->label('Newsletter')->boolean()->state(fn (Contact $record) => $record->newsletter_at !== null)->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('newsletter_at')->label('Newsletter')->nullable(),
            ])
            ->headerActions([
                Action::make('export')->label('Exporter (Excel)')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn () => Spreadsheet::contacts(Contact::query())),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [RegistrationsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'edit' => EditContact::route('/{record}/modifier'),
        ];
    }
}
