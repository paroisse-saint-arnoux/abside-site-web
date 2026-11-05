@php
    use App\Support\Markdown;
    $place = $event->place;
    $first = $upcoming->first() ?? $dates->last();
    $gallery = collect($event->gallery ?? [])->filter(fn ($g) => filled($g['path'] ?? null))->values();
    $partners = collect($event->partners ?? [])->filter(fn ($p) => filled($p['path'] ?? null))->values();
    $canRegister = $event->acceptsRegistrations() && $bookable->isNotEmpty();
    $isFull = $canRegister && collect($remaining)->every(fn ($n) => $n === 0);
@endphp
@extends('layouts.app', [
    'title' => $event->title,
    'description' => $event->intro,
    'image' => $event->shareImageUrl(),
    'imageAlt' => $event->title,
    'ogType' => 'article',
    'canonical' => $event->url(),
    'noindex' => $preview,
])

@push('head')
    @include('events.partials.json-ld')
@endpush

@section('content')
    @if ($preview)
        <div class="preview-banner">Brouillon : cette page n'est visible qu'avec le lien d'aperçu.</div>
    @endif

    <section class="event-hero">
        @if ($event->image)
            <img src="{{ Storage::url($event->image) }}" alt="{{ $event->image_alt }}" fetchpriority="high">
        @endif
        <div class="wrap">
            <div class="hero-tags">
                <a href="{{ route('agenda', ['type' => $event->type->value]) }}" class="chip t-{{ $event->type->value }}">{{ $event->type->label() }}</a>
                @foreach ($event->keywords as $k)
                    <a href="{{ $k->url() }}" class="chip is-outline">{{ $k->name }}</a>
                @endforeach
            </div>
            <h1>{{ $event->title }}</h1>
        </div>
    </section>

    <section class="event-band">
        <div class="wrap">
            <div class="band-item">
                <span class="band-label">{{ $upcoming->count() > 1 ? 'Prochaine date' : 'Date' }}</span>
                @if ($first)
                    {{ $first->fullLabel() }}
                    @if ($upcoming->count() > 1)<a href="#dates" class="link-u">{{ $upcoming->count() }} dates</a>@endif
                @else
                    Dates à venir
                @endif
            </div>
            @if ($place)
                <div class="band-item">
                    <span class="band-label">Lieu</span>
                    <span class="band-place">
                        <span>{{ $place->name }}, {{ $place->fullAddress() }}</span>
                        <a href="{{ $place->mapsUrl() }}" target="_blank" rel="noopener" class="icon-btn" aria-label="Ouvrir l'adresse dans Google Maps">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z M12 12.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                        </a>
                    </span>
                </div>
            @endif
            @if ($event->priceLabel())
                <div class="band-item"><span class="band-label">Entrée</span>{{ $event->priceLabel() }}</div>
            @endif
            @if ($canRegister)
                @if ($isFull)
                    <span class="band-full">Complet</span>
                @else
                    <a href="#inscription" class="btn-pill band-cta">S'inscrire à l'événement</a>
                @endif
            @endif
        </div>
    </section>

    <section class="wrap event-body">
        <article class="ev-text">
            @if (filled($event->intro))
                <p class="ev-intro">{{ $event->intro }}</p>
            @endif
            <div class="prose">{!! Markdown::render($event->body) !!}</div>
            @if ($specs = $event->specList())
                <dl class="ev-specs">
                    @foreach ($specs as $s)
                        <div><dt>{{ $s['key'] }}</dt><dd>{!! Markdown::renderLines($s['value']) !!}</dd></div>
                    @endforeach
                </dl>
            @endif
        </article>

        @if ($event->poster)
            <div class="ev-poster">
                <a href="{{ Storage::url($event->poster) }}" target="_blank" rel="noopener" aria-label="Voir l'affiche en grand">
                    <img src="{{ Storage::url($event->poster) }}" alt="Affiche : {{ $event->title }}" loading="lazy">
                </a>
            </div>
        @endif

        @if ($gallery->isNotEmpty())
            <div class="ev-gallery" data-carousel aria-roledescription="carrousel" aria-label="Galerie de photos">
                <div class="gallery-track">
                    @foreach ($gallery as $g)
                        <img src="{{ Storage::url($g['path']) }}" alt="{{ $g['alt'] ?? '' }}" loading="lazy">
                    @endforeach
                </div>
                <div class="gallery-dots" data-dots></div>
            </div>
        @endif
    </section>

    @if ($upcoming->count() > 1)
        <section class="wrap event-dates" id="dates">
            <h2 class="display h-m">Les dates</h2>
            <ul>
                @foreach ($upcoming as $d)
                    <li><b>{{ $d->dayLabel() }}</b>, {{ $d->timeLabel() }}@if ($d->label) — {{ $d->label }}@endif</li>
                @endforeach
            </ul>
        </section>
    @endif

    @includeWhen($canRegister, 'events.partials.registration')

    @if ($partners->isNotEmpty())
        <section class="event-partners">
            <div class="wrap">
                <h2>Avec le soutien de</h2>
                <div class="partner-logos">
                    @foreach ($partners as $p)
                        <img src="{{ Storage::url($p['path']) }}" alt="{{ $p['alt'] ?? '' }}" loading="lazy">
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
