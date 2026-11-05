@php
    $lines = ['**'.e($event->title).'**', e($date->fullLabel())];
    if ($registration->seats > 1) $lines[] = $registration->seats.' places réservées';
    if ($event->place) {
        $lines[] = e($event->place->name.', '.$event->place->fullAddress());
        $lines[] = '['.'Voir sur Google Maps'.']('.$event->place->mapsUrl().')';
    }
@endphp
<x-mail::panel>
{!! implode("<br>\n", $lines) !!}
</x-mail::panel>
