@use('App\Enums\Registration')
@use('App\Models\Registration as Booking')
@use('App\Support\Calendar')
@use('App\Support\SpamGuard')
@php
    $mine = $registered && $registered->event_id === $event->id ? $registered : null;
    $open = $bookable->filter(fn ($d) => ($remaining[$d->id] ?? null) !== 0);
    $selected = old('event_date_id', $open->count() === 1 ? $open->first()->id : null);
    $maxSeats = fn ($d) => min(Booking::MAX_SEATS, $remaining[$d->id] ?? Booking::MAX_SEATS);
@endphp
{{-- Inscription à l'événement (App\Http\Controllers\RegistrationController). --}}
<section class="event-registration" id="inscription">
    <div class="wrap reg-grid">
        <div class="reg-intro">
            <h2 class="display h-l">S'inscrire à l'événement</h2>
            <p class="reg-recap"><b>{{ $event->title }}</b><br>
                @if ($bookable->count() === 1){{ $bookable->first()->fullLabel() }}@else{{ $bookable->count() }} dates au choix @endif
                @if ($event->place)<br>{{ $event->place->name }}, {{ $event->place->city }}@endif
            </p>
            <p class="reg-mode">
                @if ($event->registration === Registration::Required)
                    Inscription obligatoire{{ $event->capacity ? ', dans la limite des places disponibles' : '' }}.
                @else
                    Inscription conseillée : elle nous aide à préparer votre accueil.
                @endif
            </p>
        </div>

        <div class="reg-card">
            @if ($mine)
                <div class="reg-done" role="status">
                    <h3>C'est noté, {{ $mine->contact->first_name }} !</h3>
                    <p>Votre inscription pour le <b>{{ $mine->session()->fullLabel() }}</b>@if ($mine->seats > 1) ({{ $mine->seats }} places)@endif est enregistrée.
                        Un e-mail de confirmation vous a été envoyé à {{ $mine->contact->email }}, et vous recevrez un rappel la veille.</p>
                    <div class="actions">
                        <a href="{{ $mine->icsUrl() }}" class="btn-pill">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4M12 13v5M9.5 15.5h5"/></svg>
                            Ajouter à mon agenda
                        </a>
                        <a href="{{ Calendar::googleUrl($event, $mine->session()) }}" class="btn-pill is-outline-dark" target="_blank" rel="noopener">Google Agenda</a>
                    </div>
                    <p class="note"><a href="{{ $event->url() }}#inscription">Inscrire une autre personne</a></p>
                </div>
            @elseif ($open->isEmpty())
                <div class="reg-done">
                    <h3>Complet</h3>
                    <p>Toutes les places ont été réservées. Pour toute question, écrivez-nous à
                        <a href="mailto:{{ config('services.contact.email') }}">{{ config('services.contact.email') }}</a>.</p>
                </div>
            @else
                <form class="form-site" method="post" action="{{ route('registrations.store', $event->slug) }}">
                    @csrf
                    @if ($bookable->count() === 1)
                        <input type="hidden" name="event_date_id" value="{{ $bookable->first()->id }}">
                    @else
                        <fieldset class="reg-dates">
                            <legend>Date</legend>
                            @foreach ($bookable as $d)
                                @php $left = $remaining[$d->id] ?? null; @endphp
                                <label class="reg-date @if ($left === 0) is-full @endif">
                                    <input type="radio" name="event_date_id" value="{{ $d->id }}" required @checked((string) $selected === (string) $d->id) @disabled($left === 0) data-max="{{ $maxSeats($d) }}">
                                    <span><b>{{ $d->dayLabel() }}</b>, {{ $d->timeLabel() }}@if ($d->label) — {{ $d->label }}@endif</span>
                                    @if ($left === 0)<em>Complet</em>@elseif ($left !== null && $left <= 10)<em>{{ $left }} place{{ $left > 1 ? 's' : '' }}</em>@endif
                                </label>
                            @endforeach
                            @error('event_date_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </fieldset>
                    @endif

                    <div class="fields">
                        <label class="field">Prénom
                            <input name="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required maxlength="80" autocomplete="given-name">
                            @error('first_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </label>
                        <label class="field">Nom
                            <input name="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required maxlength="80" autocomplete="family-name">
                            @error('last_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </label>
                        <label class="field">E-mail
                            <input name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
                            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </label>
                        <label class="field">Téléphone (facultatif)
                            <input name="phone" type="tel" class="form-control" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
                        </label>
                        <label class="field">Nombre de places
                            <select name="seats" class="form-select @error('seats') is-invalid @enderror" data-seats>
                                @php $max = $bookable->count() === 1 ? $maxSeats($bookable->first()) : Booking::MAX_SEATS; @endphp
                                @for ($i = 1; $i <= $max; $i++)
                                    <option value="{{ $i }}" @selected((int) old('seats', 1) === $i)>{{ $i }}</option>
                                @endfor
                            </select>
                            @error('seats')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <label class="check mt-3">
                        <input type="checkbox" name="newsletter" value="1" @checked(old('newsletter'))>
                        <span>Je souhaite recevoir les informations de L'Abside (une lettre de temps en temps, désinscription en un clic).</span>
                    </label>

                    @include('partials.spam-guard')

                    <div class="actions"><button type="submit" class="btn-pill">Je m'inscris</button></div>
                    <p class="note">Vos coordonnées servent à organiser l'accueil et à vous prévenir en cas de changement.
                        Elles sont conservées 3 ans après votre dernière participation. <a href="{{ route('privacy') }}">Confidentialité</a></p>
                </form>
            @endif
        </div>
    </div>
</section>
