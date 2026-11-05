<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    public function getSubheading(): ?string
    {
        return 'Les personnes inscrites à un événement. Elles sont supprimées automatiquement 3 ans après leur dernière participation.';
    }
}
