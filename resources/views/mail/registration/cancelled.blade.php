<x-mail::message>
# Inscription annulée

**{{ $contact->fullName() }}** ({{ $contact->email }}) a annulé son inscription.

@include('mail.registration._details')
</x-mail::message>
