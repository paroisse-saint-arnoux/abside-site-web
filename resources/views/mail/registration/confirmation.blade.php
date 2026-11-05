<x-mail::message>
# Bonjour {{ $registration->contact->first_name }},

Votre inscription est bien enregistrée. Nous serons heureux de vous accueillir.

@include('mail.registration._details')

@if ($event->priceLabel())
**Entrée :** {{ $event->priceLabel() }}
@endif

Vous recevrez un rappel la veille. Pour l'ajouter dès maintenant à votre agenda, ouvrez le fichier joint ou utilisez
[Google Agenda]({{ $googleUrl }}).

<x-mail::button :url="$event->url()">
Voir l'événement
</x-mail::button>

Un empêchement ? [Annulez votre inscription]({{ $registration->cancelUrl() }}) pour libérer votre place.
Une question : répondez simplement à ce message.

L'Abside
</x-mail::message>
