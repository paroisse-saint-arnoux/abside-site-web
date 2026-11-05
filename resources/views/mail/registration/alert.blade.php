@php
    $pl = fn (int $n, string $word) => $n.' '.$word.($n > 1 ? 's' : '');
    $fill = $total === null ? null : $pl($total, 'place').' réservée'.($total > 1 ? 's' : '').' pour cette date'
        .($remaining === null ? '' : ($remaining ? ', '.$pl($remaining, 'restante') : ', complet')).'.';
    $phone = $contact->phone ? ' · '.$contact->phone : '';
@endphp
<x-mail::message>
# {{ $updated ? 'Inscription modifiée' : 'Nouvelle inscription' }}

**{{ $contact->fullName() }}**<br>
{{ $contact->email }}{{ $phone }}

@include('mail.registration._details')

@if ($fill)
**{{ $fill }}**
@endif

Répondez directement à cet e-mail pour écrire à {{ $contact->first_name }}.
</x-mail::message>
