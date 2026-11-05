<?php

namespace App\Filament\Resources\Contacts\RelationManagers;

use App\Filament\Resources\Events\EventResource;
use App\Models\Registration;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Événements auxquels le contact s'est inscrit. */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Inscriptions';

    protected static ?string $modelLabel = 'inscription';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['event', 'eventDate']))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('event.title')->label('Événement')->weight('bold')
                    ->url(fn (Registration $r) => EventResource::getUrl('edit', ['record' => $r->event_id])),
                TextColumn::make('starts_at')->label('Séance')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('seats')->label('Places')->alignCenter(),
                TextColumn::make('created_at')->label('Inscrit le')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([DeleteAction::make()]);
    }
}
