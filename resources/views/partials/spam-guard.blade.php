@php $hpId = 'hp-'.Str::random(6); @endphp
{{-- Anti-spam (App\Support\SpamGuard) : champ piège invisible + horodatage chiffré. --}}
<div class="hp-field" aria-hidden="true">
    <label for="{{ $hpId }}">Ne pas remplir</label>
    <input id="{{ $hpId }}" type="text" name="website" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="_t" value="{{ \App\Support\SpamGuard::timestamp() }}">
