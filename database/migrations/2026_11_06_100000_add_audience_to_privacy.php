<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/** Politique de confidentialité : section « Mesure d'audience » (Matomo), ajoutée au texte déjà en ligne. */
return new class extends Migration
{
    public function up(): void
    {
        $legal = Setting::get('legal', []);
        $privacy = (string) ($legal['privacy'] ?? '');
        if ($privacy === '' || str_contains($privacy, 'Mesure d\'audience')) {
            return;
        }

        $section = "## Mesure d'audience\n\nNous mesurons la fréquentation du site avec **Matomo**, installé sur un serveur de la paroisse Saint-Arnoux, "
            ."sans cookie et avec des adresses IP anonymisées. Ces statistiques ne servent qu'à améliorer le site et ne sont partagées avec personne.\n\n";
        $legal['privacy'] = str_contains($privacy, '## Vos droits')
            ? str_replace('## Vos droits', $section.'## Vos droits', $privacy)
            : rtrim($privacy)."\n\n".rtrim($section);
        Setting::put('legal', $legal);
    }

    public function down(): void {}
};
