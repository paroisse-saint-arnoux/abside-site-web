@extends('layouts.app', ['description' => $home['hero']['text'] ?? null])

@php
    $hero = $home['hero'] ?? [];
    $about = $home['about'] ?? [];
    $highlight = $home['highlight'] ?? [];
    $img = fn (?string $path, string $fallback) => filled($path) ? Storage::url($path) : asset($fallback);
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $site['legal_name'] ?? config('app.name'),
        'alternateName' => config('app.name'),
        'url' => route('home'),
        'logo' => asset('images/logo-abside.png'),
        'email' => $site['email'] ?? null,
        'telephone' => $site['phone'] ?? null,
        'address' => filled($site['address'] ?? null) ? ['@type' => 'PostalAddress', 'streetAddress' => $site['address'], 'addressCountry' => 'FR'] : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    <section class="home-hero">
        <div class="wrap">
            <div class="hero-text">
                <h1 class="display h-xl">{{ $hero['title'] ?? '' }}</h1>
                @if (filled($hero['text'] ?? null))
                    <p class="lead-text">{{ $hero['text'] }}</p>
                @endif
                <a href="{{ route('agenda') }}" class="btn-pill">Voir l'agenda</a>
            </div>
            <img class="hero-img" src="{{ $img($hero['image'] ?? null, 'images/accueil-facade.jpg') }}" alt="{{ $hero['image_alt'] ?? '' }}" fetchpriority="high">
        </div>
    </section>

    @if (filled($home['doors'] ?? []))
        <div class="wrap">
            <div class="doors">
                @foreach ($home['doors'] as $door)
                    <a class="door" href="{{ $door['url'] ?? route('agenda') }}" style="background: {{ $door['color'] ?? '#152E47' }}">
                        <h2>{{ $door['title'] ?? '' }}</h2>
                        @if (filled($door['subtitle'] ?? null))<p>{{ $door['subtitle'] }}</p>@endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($featured->isNotEmpty())
        <section class="wrap home-featured">
            <div class="section-head">
                <h2 class="display h-l">À ne pas manquer</h2>
                <a href="{{ route('agenda') }}" class="link-u">Tout l'agenda</a>
            </div>
            <div class="featured-grid">
                @foreach ($featured as $event)
                    @php $date = $event->nextDate; @endphp
                    <a href="{{ $event->url() }}" class="featured-card">
                        @if ($event->image)
                            <img src="{{ Storage::url($event->image) }}" alt="{{ $event->image_alt }}" loading="lazy">
                        @else
                            <span class="img-empty" aria-hidden="true"></span>
                        @endif
                        <span class="chip t-{{ $event->type->value }}">{{ $event->type->label() }}</span>
                        <span class="card-title">{{ $event->title }}</span>
                        <span class="card-meta">{{ $date?->dayLabel(false) }}, {{ $date?->timeLabel() }}@if ($event->place)<br>{{ $event->place->name }}, {{ $event->place->city }}@endif</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if (! empty($highlight['enabled']) && filled($highlight['title'] ?? null))
        <section class="night-band">
            @if (filled($highlight['image'] ?? null))<img src="{{ Storage::url($highlight['image']) }}" alt="{{ $highlight['image_alt'] ?? '' }}" loading="lazy">@endif
            <div class="wrap">
                <div class="night-text">
                    @if (filled($highlight['label'] ?? null))<span class="chip is-ochre">{{ $highlight['label'] }}</span>@endif
                    <h2 class="display h-xl">{{ $highlight['title'] }}</h2>
                    @if (filled($highlight['text'] ?? null))<p>{{ $highlight['text'] }}</p>@endif
                    @if (filled($highlight['url'] ?? null))<a href="{{ $highlight['url'] }}" class="btn-pill is-ochre">{{ $highlight['button'] ?? 'Découvrir' }}</a>@endif
                </div>
            </div>
        </section>
    @endif

    @if (filled($about['title'] ?? null))
        <section class="home-about">
            <div class="wrap">
                <img src="{{ $img($about['image'] ?? null, 'images/accueil-chapelle.jpg') }}" alt="{{ $about['image_alt'] ?? '' }}" loading="lazy">
                <div class="about-text">
                    <h2 class="display h-l">{{ $about['title'] }}</h2>
                    <div class="prose" style="color: inherit">{!! \App\Support\Markdown::render($about['text'] ?? null) !!}</div>
                    <a href="{{ route('about') }}" class="link-u">Qui sommes-nous</a>
                </div>
            </div>
        </section>
    @endif
@endsection
