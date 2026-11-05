@php
    $siteName = config('app.name');
    $pageTitle = isset($title) && filled($title) ? $title.' · '.$siteName : $siteName.' — Centre culturel et touristique catholique des Alpes-Maritimes';
    $pageDescription = Str::limit(strip_tags($description ?? ($site['description'] ?? '')), 300);
    $pageImage = $image ?? asset('images/og-abside.jpg');
    $canonical = $canonical ?? url()->current();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="#E9BE4A">
    @if (! empty($noindex) || parse_url(config('app.url'), PHP_URL_HOST) !== config('app.public_host'))
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">

    {{-- Open Graph (Facebook, WhatsApp, LinkedIn) --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ filled($title ?? null) ? $title : $siteName }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @isset($imageAlt)<meta property="og:image:alt" content="{{ $imageAlt }}">@endisset
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ filled($title ?? null) ? $title : $siteName }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    @stack('head')

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="{{ $bodyClass ?? '' }}">
    <a href="#main" class="visually-hidden-focusable">Aller au contenu</a>
    @include('partials.header')

    <main id="main">
        @yield('content')
    </main>

    @unless (! empty($bare))
        @include('partials.newsletter')
    @endunless
    @include('partials.footer')
    @stack('scripts')
</body>
</html>
