{{-- Une séance : bloc date (couleur du type), textes, visuel à droite. Utilisé par l'agenda et les pages de mot clé. --}}
@php $e = $date->event; @endphp
<a href="{{ $e->url() }}" class="event-row">
    <span class="date-block t-{{ $e->type->value }}">
        <span class="wd">{{ Str::ucfirst($date->starts_at->translatedFormat('D')) }}</span>
        <span class="day">{{ $date->starts_at->day }}</span>
        <span class="mon">{{ $date->starts_at->translatedFormat('M') }}</span>
    </span>
    <span class="row-text">
        <span class="row-tags">
            <span class="type-label t-{{ $e->type->value }}">{{ $e->type->label() }}</span>
            @foreach ($e->keywords as $k)<span class="chip">{{ $k->name }}</span>@endforeach
        </span>
        <span class="row-title">{{ $date->label ?: $e->title }}</span>
        <span class="row-meta">{{ $date->timeLabel() }}@if ($e->place) · {{ $e->place->name }}, {{ $e->place->city }}@endif @if ($date->label) · {{ $e->title }}@endif</span>
    </span>
    @if ($e->image)
        <img class="row-img" src="{{ Storage::url($e->image) }}" alt="" loading="lazy">
    @else
        <span class="row-img is-empty" aria-hidden="true"></span>
    @endif
</a>
