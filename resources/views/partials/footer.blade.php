<footer class="site-footer">
    <div class="wrap">
        <div class="foot-top">
            <img class="foot-logo" src="{{ asset('images/logo-abside-blanc.svg') }}" alt="L'Abside" width="102" height="72" loading="lazy">
            <nav class="foot-links" aria-label="Pied de page">
                <a href="{{ route('about') }}">Qui sommes-nous</a>
                <a href="{{ route('contact') }}">Contact et presse</a>
                <a href="{{ route('support') }}">Soutenir l'association</a>
                @if (filled($site['facebook_url'] ?? null))
                    <a href="{{ $site['facebook_url'] }}" target="_blank" rel="noopener">Facebook</a>
                @endif
                @if (filled($site['instagram_url'] ?? null))
                    <a href="{{ $site['instagram_url'] }}" target="_blank" rel="noopener">Instagram</a>
                @endif
            </nav>
            <div class="contact-lines">
                @if (filled($site['phone'] ?? null))
                    <a href="tel:{{ preg_replace('/\D/', '', $site['phone']) }}">{{ $site['phone'] }}</a>
                @endif
                @if (filled($site['email'] ?? null))
                    <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>
                @endif
            </div>
        </div>
        <div class="foot-bot">
            <span>{{ $site['legal_name'] ?? "L'Abside" }}{{ filled($site['address'] ?? null) ? ', '.$site['address'] : '' }}</span>
            <span><a href="{{ route('legal') }}">Mentions légales</a> · <a href="{{ route('privacy') }}">Confidentialité</a></span>
        </div>
    </div>
</footer>
