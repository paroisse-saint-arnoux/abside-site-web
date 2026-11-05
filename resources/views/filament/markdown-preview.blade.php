{{-- Aperçu du texte Markdown dans l'admin, avec les styles du site (feuille isolée preview.scss). --}}
<div>
    @vite(['resources/scss/preview.scss'])
    <div class="ab-preview" style="background:#FDF8EC;border-radius:12px;padding:28px 32px;max-height:70vh;overflow:auto">
        <div class="prose">
            @if (filled($html))
                {!! $html !!}
            @else
                <p style="color:#4a463c;font-style:italic">Rien à afficher pour le moment.</p>
            @endif
        </div>
    </div>
</div>
