@use('App\Support\SpamGuard')
@php $press = $pages['press'] ?? []; @endphp
@extends('layouts.app', [
    'title' => 'Contact et presse',
    'description' => "Une question sur un événement de L'Abside, une demande de la presse ? Écrivez-nous.",
])

@if (SpamGuard::turnstileEnabled())
    @push('head')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif

@section('content')
    <section class="page-head">
        <div class="wrap">
            <h1>Contact et presse</h1>
            <p class="lead-text">Une question sur un événement, une proposition, une demande de la presse ? Nous vous répondons rapidement.</p>
        </div>
    </section>

    <section class="page-body">
        <div class="wrap contact-grid">
            <aside>
                <h2>{{ $site['legal_name'] ?? "L'Abside" }}</h2>
                <div class="contact-lines">
                    @if (filled($site['address'] ?? null))<span>{{ $site['address'] }}</span>@endif
                    @if (filled($site['phone'] ?? null))<a href="tel:{{ preg_replace('/\D/', '', $site['phone']) }}">{{ $site['phone'] }}</a>@endif
                    @if (filled($site['email'] ?? null))<a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>@endif
                </div>
                @if (filled($press['body'] ?? null))
                    <h2>{{ $press['title'] ?? 'Presse' }}</h2>
                    <div class="prose">{!! \App\Support\Markdown::render($press['body']) !!}</div>
                @endif
            </aside>

            <div class="contact-card">
                @if (session('contact-sent'))
                    <div class="alert-site ok" role="status">Merci, votre message a bien été envoyé. Nous vous répondrons très vite.</div>
                @endif

                <form class="form-site" method="post" action="{{ route('contact.send') }}">
                    @csrf
                    <div class="fields">
                        <label class="field">Nom et prénom
                            <input name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </label>
                        <label class="field">E-mail
                            <input name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
                            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label class="field mt-3">Objet (facultatif)
                        <input name="subject" type="text" class="form-control" value="{{ old('subject') }}" maxlength="190">
                    </label>
                    <label class="field mt-3">Message
                        <textarea name="message" rows="7" class="form-control @error('message') is-invalid @enderror" required maxlength="5000">{{ old('message') }}</textarea>
                        @error('message')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </label>

                    @include('partials.spam-guard')

                    @if (SpamGuard::turnstileEnabled())
                        <div class="cf-turnstile mt-3" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="fr"></div>
                    @endif

                    <div class="actions"><button type="submit" class="btn-pill">Envoyer mon message</button></div>
                    <p class="note">Vos coordonnées servent uniquement à vous répondre. <a href="{{ route('privacy') }}">Confidentialité</a></p>
                </form>
            </div>
        </div>
    </section>
@endsection
