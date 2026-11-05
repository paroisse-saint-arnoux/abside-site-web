@extends('layouts.app', [
    'title' => 'Agenda',
    'description' => "Les prochains rendez-vous de L'Abside : théâtre, conférences, concerts, expositions et visites guidées dans les églises des Alpes-Maritimes.",
    'canonical' => route('agenda', array_filter(['type' => $agenda->filters['type'], 'mot-cle' => $agenda->filters['mot-cle']])),
    'noindex' => filled($agenda->filters['zone']) || filled($agenda->filters['lieu']) || filled($agenda->filters['mois']),
    'schema' => [
        'type' => 'CollectionPage',
        'breadcrumbs' => array_values(array_filter([
            ['Agenda', route('agenda')],
            ($t = \App\Enums\EventType::tryFrom((string) $agenda->filters['type'])) ? [$t->menuLabel(), route('agenda', ['type' => $t->value])] : null,
        ])),
        'mainEntity' => ['@id' => route('agenda').'#seances'],
        'nodes' => [\App\Support\StructuredData::eventList($rows->map->event, route('agenda').'#seances', $agenda->past ? 'Événements passés' : 'Prochains événements')],
    ],
])

@section('content')
    <section class="page-head agenda-head">
        <div class="wrap">
            <h1>Agenda</h1>
            <div class="period-toggle" role="group" aria-label="Période">
                <a href="{{ $agenda->url(past: false) }}" @class(['active' => ! $agenda->past]) data-agenda-link data-period="upcoming">À venir</a>
                <a href="{{ $agenda->url(past: true) }}" @class(['active' => $agenda->past]) data-agenda-link data-period="past">Passés</a>
            </div>
        </div>
    </section>

    <div class="wrap agenda" data-agenda data-url="{{ route('agenda') }}">
        <aside class="agenda-aside">
            <div class="agenda-map-wrap">
                <div class="agenda-map" id="agenda-map" data-pins='@json($pins)' data-zone="{{ $agenda->filters['zone'] }}" role="region" aria-label="Carte des lieux des événements"></div>
                <p class="map-hint">Déplacez ou zoomez la carte pour chercher par lieu.</p>
            </div>
            <div data-agenda-filters>@include('agenda.partials.filters')</div>
        </aside>

        <div class="agenda-results" data-agenda-results aria-live="polite">
            @include('agenda.partials.results')
        </div>
    </div>
@endsection
