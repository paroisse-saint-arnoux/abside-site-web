@extends('layouts.app', ['title' => 'Annuler mon inscription', 'noindex' => true])

@section('content')
    <section class="page-head">
        <div class="wrap">
            <h1>Annuler mon inscription</h1>
        </div>
    </section>
    <section class="page-body">
        <div class="wrap cancel-box">
            @if (session('cancelled'))
                <div class="alert-site ok" role="status">Votre inscription à « {{ session('cancelled') }} » est annulée. Merci de nous avoir prévenus.</div>
                <a href="{{ route('agenda') }}" class="btn-pill">Voir l'agenda</a>
            @elseif (! $registration)
                <p>Cette inscription n'existe plus : elle a sans doute déjà été annulée.</p>
                <a href="{{ route('agenda') }}" class="btn-pill">Voir l'agenda</a>
            @elseif ($registration->starts_at->isPast())
                <p>Cet événement a déjà eu lieu.</p>
                <a href="{{ route('agenda') }}" class="btn-pill">Voir l'agenda</a>
            @else
                <p><b>{{ $registration->event->title }}</b><br>
                    {{ $registration->session()->fullLabel() }}@if ($registration->event->place) · {{ $registration->event->place->name }}, {{ $registration->event->place->city }}@endif<br>
                    Au nom de {{ $registration->contact->fullName() }}@if ($registration->seats > 1), {{ $registration->seats }} places @endif</p>
                <form method="post" action="{{ route('registrations.destroy', $registration->token) }}" class="actions-row">
                    @csrf
                    <button type="submit" class="btn-pill">Annuler mon inscription</button>
                    <a href="{{ $registration->event->url() }}" class="link-u">Garder ma place</a>
                </form>
            @endif
        </div>
    </section>
@endsection
