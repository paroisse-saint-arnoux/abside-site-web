@php $chips = $agenda->chips(); $spot = $agenda->spotlight(); @endphp
@if ($chips->isNotEmpty())
    <div class="active-filters">
        @foreach ($chips as $c)
            <a href="{{ $c->url }}" class="active-chip" data-agenda-link @if ($c->key === 'zone') data-clear-zone @endif aria-label="Retirer le filtre {{ $c->label }}">
                {{ $c->label }}
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </a>
        @endforeach
        <a href="{{ $agenda->url(array_fill_keys(\App\Support\Agenda::FILTERS, null)) }}" class="clear-all" data-agenda-link data-clear-zone>Tout effacer</a>
    </div>
@endif

@if ($spot)
    @php $e = $spot->event; @endphp
    <a href="{{ $e->url() }}" class="spotlight">
        @if ($e->image)<img src="{{ Storage::url($e->image) }}" alt="{{ $e->image_alt }}">@endif
        <div class="spotlight-text">
            <span class="chip t-{{ $e->type->value }}">{{ $e->type->label() }}</span>
            <h2>{{ $spot->label ?: $e->title }}</h2>
            <p>{{ $spot->fullLabel() }}@if ($e->place) · {{ $e->place->name }}, {{ $e->place->city }}@endif</p>
        </div>
    </a>
@endif

<p class="results-count">{{ $total }} {{ $total > 1 ? 'dates' : 'date' }}{{ $agenda->past ? ' passées' : '' }}</p>

<div class="event-rows">
    @forelse ($rows as $date)
        @include('agenda.partials.row', ['date' => $date])
    @empty
        <p class="empty">Aucun événement pour cette sélection.</p>
    @endforelse
</div>

@if ($more)
    <div class="more"><a href="{{ $more }}" class="btn-pill" data-agenda-link>Voir plus de dates</a></div>
@endif
