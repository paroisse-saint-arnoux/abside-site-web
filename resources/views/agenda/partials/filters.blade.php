@php $types = $agenda->types(); $keywords = $agenda->keywords(); $places = $agenda->places(); @endphp
<div class="filter-group">
    <h2>Type d'événement</h2>
    <div class="filter-chips">
        @forelse ($types as $b)
            <a href="{{ $b->url }}" @class(['filter-chip', 'active' => $b->active]) @if ($b->active) style="--chip: {{ \App\Enums\EventType::from($b->value)->color() }}" aria-current="true" @endif data-agenda-link>{{ $b->label }} <span>{{ $b->count }}</span></a>
        @empty
            <p class="muted">Aucun événement.</p>
        @endforelse
    </div>
</div>

<form class="filter-group" method="get" action="{{ route('agenda') }}" data-agenda-month>
    @foreach ($agenda->filters as $key => $value)
        @if ($key !== 'mois' && filled($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    @if ($agenda->past)<input type="hidden" name="passes" value="1">@endif
    <label for="agenda-month"><h2>Mois</h2></label>
    <select id="agenda-month" name="mois" class="form-select">
        <option value="">Tous les mois</option>
        @foreach ($agenda->months() as $m)
            <option value="{{ $m->value }}" @selected($agenda->filters['mois'] === $m->value)>{{ $m->label }} ({{ $m->count }})</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="btn-pill is-small mt-2">Filtrer</button></noscript>
</form>

@if ($keywords->isNotEmpty())
    <div class="filter-group">
        <h2>Mots clés</h2>
        <div class="filter-chips">
            @foreach ($keywords as $b)
                <a href="{{ $b->url }}" @class(['filter-chip', 'active' => $b->active]) @if ($b->active) aria-current="true" @endif data-agenda-link>{{ $b->label }} <span>{{ $b->count }}</span></a>
            @endforeach
        </div>
    </div>
@endif

@if ($places->isNotEmpty())
    <div class="filter-group">
        <h2>Lieux</h2>
        <div class="filter-chips">
            @foreach ($places as $b)
                <a href="{{ $b->url }}" @class(['filter-chip', 'active' => $b->active]) @if ($b->active) aria-current="true" @endif data-agenda-link>{{ $b->label }} <span>{{ $b->count }}</span></a>
            @endforeach
        </div>
    </div>
@endif
