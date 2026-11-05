<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Registration;
use App\Support\Booking;
use App\Support\Spreadsheet;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Inscrits d'un événement : liste par séance, ajout manuel (inscription par téléphone), export Excel. */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Inscriptions';

    protected static ?string $modelLabel = 'inscription';

    protected static ?string $pluralModelLabel = 'inscriptions';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $seats = (int) $ownerRecord->registrations()->sum('seats');

        return $seats ? (string) $seats : null;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Event && ($ownerRecord->acceptsRegistrations() || $ownerRecord->registrations()->exists());
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['contact', 'eventDate']))
            ->defaultSort('starts_at')
            ->emptyStateHeading('Aucune inscription pour le moment')
            ->description(fn () => $this->summary($event))
            ->columns([
                TextColumn::make('starts_at')->label('Séance')->dateTime('d/m/Y H:i')->sortable()
                    ->description(fn (Registration $r) => $r->eventDate?->label),
                TextColumn::make('contact.last_name')->label('Nom')->searchable()->sortable()
                    ->formatStateUsing(fn (Registration $r) => $r->contact->fullName())
                    ->url(fn (Registration $r) => ContactResource::getUrl('edit', ['record' => $r->contact_id])),
                TextColumn::make('contact.email')->label('E-mail')->searchable()->copyable(),
                TextColumn::make('contact.phone')->label('Téléphone')->placeholder('—'),
                TextColumn::make('seats')->label('Places')->alignCenter()->sortable(),
                TextColumn::make('created_at')->label('Inscrit le')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('event_date_id')->label('Séance')
                    ->options(fn () => $event->dates->mapWithKeys(fn (EventDate $d) => [$d->id => $d->starts_at->format('d/m/Y H:i').($d->label ? ' — '.$d->label : '')])),
            ])
            ->headerActions([
                Action::make('add')->label('Ajouter une inscription')->icon('heroicon-o-user-plus')
                    ->modalDescription('Pour une inscription reçue par téléphone ou par e-mail. Aucun e-mail n\'est envoyé au participant.')
                    ->schema([
                        Select::make('event_date_id')->label('Séance')->required()->native(false)
                            ->options(fn () => $event->dates()->get()->mapWithKeys(fn (EventDate $d) => [$d->id => $d->fullLabel().($d->label ? ' — '.$d->label : '')]))
                            ->default(fn () => $event->dates()->upcoming()->value('id')),
                        Grid::make(2)->schema([
                            TextInput::make('first_name')->label('Prénom')->required()->maxLength(80),
                            TextInput::make('last_name')->label('Nom')->required()->maxLength(80),
                            TextInput::make('email')->label('E-mail')->email()->required()->maxLength(190),
                            TextInput::make('phone')->label('Téléphone')->tel()->maxLength(30),
                            TextInput::make('seats')->label('Places')->numeric()->minValue(1)->maxValue(100)->default(1)->required(),
                        ]),
                        Toggle::make('ignore_capacity')->label('Dépasser la jauge si besoin'),
                    ])
                    ->action(function (array $data, Action $action) use ($event) {
                        $date = $event->dates()->findOrFail($data['event_date_id']);
                        try {
                            Booking::book($event, $date, [...$data, 'seats' => (int) $data['seats']], ! $data['ignore_capacity']);
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            Notification::make()->title($e->errors()['seats'][0] ?? 'Inscription impossible')->danger()->send();
                            $action->halt();
                        }
                        Notification::make()->title('Inscription ajoutée')->success()->send();
                    }),
                Action::make('export')->label('Exporter (Excel)')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn () => Spreadsheet::registrations($event)),
            ])
            ->recordActions([
                EditAction::make()->label('Places')
                    ->schema([TextInput::make('seats')->label('Places')->numeric()->minValue(1)->maxValue(100)->required()]),
                DeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    /** « 42 places réservées · jauge : 80 par date » */
    private function summary(Event $event): ?string
    {
        $seats = (int) $event->registrations()->sum('seats');
        if (! $seats && ! $event->capacity) {
            return null;
        }

        return $seats.' place'.($seats > 1 ? 's' : '').' réservée'.($seats > 1 ? 's' : '')
            .($event->capacity ? ' · jauge : '.$event->capacity.' par date' : '');
    }
}
