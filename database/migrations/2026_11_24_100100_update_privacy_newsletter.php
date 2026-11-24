<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/** Politique de confidentialité : les inscrits à la lettre d'information sont aussi enregistrés sur le site, jusqu'à leur désinscription. */
return new class extends Migration
{
    private const OLD = "Si vous vous y inscrivez, votre prénom, votre nom et votre adresse e-mail sont enregistrés chez notre prestataire d'e-mailing **Brevo** (Sendinblue SAS, France) pour vous envoyer nos informations. Chaque lettre contient un lien de désinscription.";

    private const NEW = "Si vous vous y inscrivez, votre prénom, votre nom et votre adresse e-mail sont enregistrés sur le site et chez notre prestataire d'e-mailing **Brevo** (Sendinblue SAS, France) pour vous envoyer nos informations. Ils sont conservés jusqu'à votre désinscription : chaque lettre contient un lien pour vous désinscrire.";

    public function up(): void
    {
        $legal = Setting::get('legal', []);
        $privacy = (string) ($legal['privacy'] ?? '');
        if (! str_contains($privacy, self::OLD)) {
            return; // texte déjà retouché dans l'admin : on n'y touche pas
        }
        $legal['privacy'] = str_replace(self::OLD, self::NEW, $privacy);
        Setting::put('legal', $legal);
    }

    public function down(): void {}
};
