@php use App\Enums\EventType; @endphp
<header class="site-header">
    <div class="wrap">
        <div class="nav-top">
            <a href="{{ route('home') }}" class="brand" aria-label="L'Abside, accueil">
                <img src="{{ asset('images/logo-abside.svg') }}" alt="L'Abside" width="102" height="72">
            </a>
            <div class="nav-actions">
                <a href="{{ route('agenda') }}" class="nav-agenda">Tout l'agenda</a>
                <a href="{{ route('support') }}" class="btn-pill is-small">Soutenir</a>
            </div>
        </div>
        <nav class="nav-types" aria-label="Types d'événement">
            @foreach (EventType::cases() as $type)
                @if ($loop->index === 3)<span class="nav-break" aria-hidden="true"></span>@endif
                <a href="{{ route('agenda', ['type' => $type->value]) }}" @class(['active' => request('type') === $type->value])>{{ $type->menuLabel() }}</a>
            @endforeach
        </nav>
    </div>
</header>
