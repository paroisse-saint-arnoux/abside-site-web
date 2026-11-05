<x-mail::message>
# Bonjour {{ $registration->contact->first_name }},

Nous vous attendons demain :

@include('mail.registration._details')

<x-mail::button :url="$event->url()">
Voir l'événement
</x-mail::button>

Un empêchement ? [Annulez votre inscription]({{ $registration->cancelUrl() }}) pour libérer votre place.

À demain,<br>
L'Abside
</x-mail::message>
