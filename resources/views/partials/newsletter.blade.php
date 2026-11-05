{{-- Lettre d'information (Brevo), présente sur toutes les pages. --}}
<section class="newsletter-band" id="newsletter">
    <div class="wrap">
        <div>
            <h2 class="display">{{ $site['newsletter_title'] ?? "Recevoir les informations de L'Abside" }}</h2>
            @if (filled($site['newsletter_text'] ?? null))
                <p>{{ $site['newsletter_text'] }}</p>
            @endif
        </div>
        <form class="form-site" method="post" action="{{ route('newsletter') }}" data-newsletter>
            @csrf
            @include('partials.spam-guard')
            <div class="form-inline-site">
                <label class="field">Prénom<input class="form-control" type="text" name="first_name" autocomplete="given-name" required maxlength="80"></label>
                <label class="field">Nom<input class="form-control" type="text" name="last_name" autocomplete="family-name" required maxlength="80"></label>
                <label class="field">E-mail<input class="form-control" type="email" name="email" autocomplete="email" required maxlength="190"></label>
                <button type="submit" class="btn-pill">S'inscrire</button>
            </div>
            <p class="form-feedback" data-feedback role="status" aria-live="polite">
                @if (session('newsletter')){{ session('newsletter')['message'] }}@endif
            </p>
        </form>
    </div>
</section>
