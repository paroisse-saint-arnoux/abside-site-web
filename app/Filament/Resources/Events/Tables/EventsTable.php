<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\EventType;
use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Clic sur la ligne = édition ; la page publique s'ouvre via « Voir ».
            ->recordUrl(fn (Event $record) => EventResource::getUrl('edit', ['record' => $record]))
            ->modifyQueryUsing(fn ($query) => $query->with(['place', 'nextDate'])->withCount('dates')->withSum('registrations as seats_taken', 'seats')
                ->addSelect(['next_starts_at' => \App\Models\EventDate::select('starts_at')->whereColumn('event_id', 'events.id')
                    ->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at')->limit(1)]))
            ->defaultSort(fn ($query) => $query->orderByRaw('next_starts_at IS NULL')->orderBy('next_starts_at')->orderByDesc('updated_at'))
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->square()->imageSize(48),
                TextColumn::make('title')->label('Titre')->searchable()->wrap()
                    ->description(fn (Event $record) => $record->place ? $record->place->name.', '.$record->place->city : null),
                TextColumn::make('type')->label('Type')->badge()
                    ->formatStateUsing(fn (EventType $state) => $state->label())
                    ->color('gray'),
                TextColumn::make('next_starts_at')->label('Prochaine date')->dateTime('d/m/Y H:i')->placeholder('Passé')
                    ->description(fn (Event $record) => $record->dates_count > 1 ? $record->dates_count.' dates' : null),
                TextColumn::make('seats_taken')->label('Inscrits')->alignCenter()->placeholder('—')
                    ->formatStateUsing(fn ($state) => (int) $state ?: null),
                IconColumn::make('is_published')->label('Publié')->boolean(),
                IconColumn::make('is_featured')->label('Accueil')->boolean()->trueIcon('heroicon-s-star')->falseIcon('')->trueColor('warning'),
                IconColumn::make('is_spotlight')->label('Exergue')->boolean()->trueIcon('heroicon-s-sparkles')->falseIcon('')->trueColor('warning'),
                TextColumn::make('updated_at')->label('Modifié le')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')->options(EventType::options()),
                TernaryFilter::make('is_published')->label('Publié'),
                TernaryFilter::make('upcoming')->label('Période')->placeholder('Tous')->trueLabel('À venir')->falseLabel('Passés')
                    ->queries(
                        true: fn ($q) => $q->whereHas('dates', fn ($d) => $d->upcoming()),
                        false: fn ($q) => $q->whereDoesntHave('dates', fn ($d) => $d->upcoming()),
                    ),
            ])
            ->recordActions([
                Action::make('voir')->label('Voir')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Event $record) => $record->is_published ? $record->url() : $record->previewUrl())->openUrlInNewTab(),
                // Pour la saison suivante : tout est copié sauf les dates, et la copie reste en brouillon.
                Action::make('duplicate')->label('Dupliquer')->icon('heroicon-o-document-duplicate')->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('La copie reprend tout sauf les dates, et reste en brouillon.')
                    ->action(function (Event $record, $livewire) {
                        $copy = $record->replicate(['slug', 'preview_token', 'next_starts_at', 'dates_count', 'seats_taken']);
                        $copy->title = $record->title;
                        $copy->slug = $record->slug.'-copie';
                        $copy->is_published = false;
                        $copy->is_featured = false;
                        $copy->is_spotlight = false;
                        $copy->save();
                        $copy->keywords()->sync($record->keywords()->pluck('keywords.id'));
                        Notification::make()->title('Événement dupliqué')->success()->send();
                        $livewire->redirect(EventResource::getUrl('edit', ['record' => $copy]));
                    }),
                Action::make('export')->label('Inscrits (Excel)')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->visible(fn (Event $record) => $record->seats_taken > 0)
                    ->action(fn (Event $record) => \App\Support\Spreadsheet::registrations($record)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
