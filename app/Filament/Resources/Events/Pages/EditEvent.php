<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    /** En bas : Supprimer à gauche, Annuler / Enregistrer à droite. */
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            DeleteAction::make()->extraAttributes(['style' => 'margin-inline-end: auto']),
        ];
    }

    /** Les séances générées (horaires récurrents) sont recalculées après l'enregistrement : on recharge le formulaire. */
    protected function afterSave(): void
    {
        $this->record->refresh();
    }
}
