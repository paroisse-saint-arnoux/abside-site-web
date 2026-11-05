{{-- Inscription à l'événement : formulaire branché à l'étape « Inscriptions et contacts ». --}}
<section class="event-registration" id="inscription">
    <div class="wrap">
        <h2 class="display h-l">S'inscrire à l'événement</h2>
        <p class="reg-recap"><b>{{ $event->title }}</b><br>{{ $first?->fullLabel() }}@if ($event->place) · {{ $event->place->name }}, {{ $event->place->city }}@endif</p>
    </div>
</section>
