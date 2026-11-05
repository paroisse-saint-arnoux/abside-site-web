@php
    use App\Support\Markdown;
    $gallery = collect($keyword->gallery ?? [])->filter(fn ($g) => filled($g['path'] ?? null))->values();
@endphp
@extends('layouts.app', [
    'title' => $keyword->name,
    'description' => $keyword->intro,
    'image' => $keyword->image ? Storage::url($keyword->image) : null,
    'imageAlt' => $keyword->image_alt,
])

@section('content')
    <section class="event-hero">
        @if ($keyword->image)
            <img src="{{ Storage::url($keyword->image) }}" alt="{{ $keyword->image_alt }}" fetchpriority="high">
        @endif
        <div class="wrap">
            <h1>{{ $keyword->name }}</h1>
        </div>
    </section>

    @if (filled($keyword->when_label) || filled($keyword->where_label) || $dates->isNotEmpty())
        <section class="event-band">
            <div class="wrap">
                @if (filled($keyword->when_label))<div class="band-item"><span class="band-label">Dates</span>{{ $keyword->when_label }}</div>@endif
                @if (filled($keyword->where_label))<div class="band-item"><span class="band-label">Où</span>{{ $keyword->where_label }}</div>@endif
                @if ($dates->isNotEmpty())<a href="#programme" class="btn-pill band-cta">Voir le programme</a>@endif
            </div>
        </section>
    @endif

    <section class="wrap event-body">
        <article class="ev-text">
            @if (filled($keyword->intro))<p class="ev-intro">{{ $keyword->intro }}</p>@endif
            <div class="prose">{!! Markdown::render($keyword->body) !!}</div>
        </article>
        @if ($gallery->isNotEmpty())
            <div class="ev-gallery is-side" data-carousel aria-roledescription="carrousel" aria-label="Galerie de photos">
                <div class="gallery-track">
                    @foreach ($gallery as $g)<img src="{{ Storage::url($g['path']) }}" alt="{{ $g['alt'] ?? '' }}" loading="lazy">@endforeach
                </div>
                <div class="gallery-dots" data-dots></div>
            </div>
        @endif
    </section>

    <section class="wrap keyword-events" id="programme">
        <div class="section-head">
            <h2 class="display h-l">Les événements</h2>
            <a href="{{ route('agenda', ['mot-cle' => $keyword->slug]) }}" class="link-u">Voir dans l'agenda</a>
        </div>
        <div class="event-rows">
            @forelse ($dates as $date)
                @include('agenda.partials.row', ['date' => $date])
            @empty
                <p class="empty">Le programme sera publié prochainement.</p>
            @endforelse
        </div>
    </section>
@endsection
