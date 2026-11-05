<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Images;
use Illuminate\Database\Seeder;

/**
 * Contenu de départ (textes fournis par l'association, photos du dossier « Assets et photos »).
 * Idempotent : ne crée que ce qui n'existe pas encore.
 */
class InitialContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();

        foreach (['home', 'site', 'pages', 'legal'] as $key) {
            if (! Setting::find($key)) {
                Setting::put($key, $this->{$key}());
            }
        }
    }

    private function seedAdmin(): void
    {
        $email = config('services.admin_email');

        if (blank($email) || User::exists()) {
            return;
        }

        User::create(['name' => 'Administrateur', 'email' => mb_strtolower($email), 'role' => Role::Admin, 'is_active' => true]);
    }

    private function home(): array
    {
        return [
            'hero' => [
                'title' => 'Patrimoine, culture et création contemporaine',
                'text' => "L'Abside fait découvrir le patrimoine religieux et culturel du diocèse de Nice, à travers des expositions, visites, conférences et spectacles ouverts à tous.",
                'image' => Images::store(public_path('images/accueil-facade.jpg'), 'accueil'),
                'image_alt' => "Façade jaune d'une église sur un ciel bleu",
            ],
            'doors' => [
                ['title' => 'Théâtre', 'subtitle' => 'Faire vivre le patrimoine religieux par le spectacle vivant.', 'color' => '#1F4E8C', 'url' => '/agenda?type=theatre'],
                ['title' => 'Conférences', 'subtitle' => 'Histoire, art et spiritualité en miroir. Gratuites et ouvertes à tous.', 'color' => '#B4492C', 'url' => '/agenda?type=conference'],
                ['title' => 'Visites guidées', 'subtitle' => "Tout au long de l'année, dans les églises des Alpes-Maritimes.", 'color' => '#2F5D50', 'url' => '/agenda?type=visite'],
                ['title' => 'Nuit des églises', 'subtitle' => 'Une manifestation estivale, inscrite dans les diocèses depuis 2011.', 'color' => '#152E47', 'url' => '/mot-cle/nuit-des-eglises'],
            ],
            'about' => [
                'title' => "Faire dialoguer la tradition et l'inspiration d'aujourd'hui",
                'text' => "Lieu de rencontre entre patrimoine, culture et création contemporaine, L'Abside encourage l'expression artistique sous toutes ses formes. Théâtre, littérature, arts plastiques, musique ou créations pluridisciplinaires participent ainsi à faire dialoguer la tradition et l'inspiration d'aujourd'hui.",
                'image' => Images::store(public_path('images/accueil-chapelle.jpg'), 'accueil'),
                'image_alt' => 'Chapelle baroque',
            ],
        ];
    }

    private function site(): array
    {
        return [
            'description' => "L'Abside, centre culturel et touristique catholique des Alpes-Maritimes : théâtre, conférences, concerts, expositions et visites guidées dans les églises du diocèse de Nice.",
            'legal_name' => "L'Abside, centre culturel et touristique catholique des Alpes-Maritimes",
            'address' => '23 avenue Sévigné, 06100 Nice',
            'email' => 'abside.association@gmail.com',
            'phone' => '06 14 47 30 42',
            'newsletter_title' => "Recevoir les informations de L'Abside",
            'newsletter_text' => null,
        ];
    }

    private function pages(): array
    {
        return [
            'about' => [
                'title' => 'Qui sommes-nous',
                'intro' => "L'Abside est un centre culturel et touristique dédié à la découverte du patrimoine religieux et culturel du diocèse de Nice.",
                'body' => <<<'MD'
À travers des expositions, visites, conférences, spectacles et événements ouverts à tous, L'Abside met en lumière la richesse de l'héritage architectural, artistique et spirituel du territoire.

Lieu de rencontre entre patrimoine, culture et création contemporaine, L'Abside encourage également l'expression artistique sous toutes ses formes. Théâtre, littérature, arts plastiques, musique ou créations pluridisciplinaires participent ainsi à faire dialoguer la tradition et l'inspiration d'aujourd'hui.

## Le Théâtre de l'Abside

Le Théâtre de l'Abside est un projet culturel qui fait dialoguer création artistique et patrimoine religieux. À travers une programmation de spectacles vivants accueillis dans des édifices remarquables, il propose aux habitants une expérience culturelle originale tout en contribuant à la valorisation du patrimoine.

## Les conférences

Nos conférences visent à transmettre la beauté de l'art par une compréhension intégrale de l'œuvre : histoire, art et spiritualité sont ainsi abordés en miroir. Les conférences sont gratuites et ouvertes à tous.

## La Nuit des églises

La Nuit des églises, initiée par l'Église de France, est une manifestation cultuelle et culturelle inscrite dans le paysage estival des diocèses depuis 2011, bénéficiant d'une reconnaissance à l'échelle nationale, et à laquelle participent chaque année plusieurs centaines d'églises.

## Les visites guidées

Tout au long de l'année, des visites guidées gratuites et ouvertes à tous sont proposées dans les églises du département des Alpes-Maritimes.
MD,
            ],
            'support' => [
                'title' => "Soutenir l'association",
                'intro' => "La plupart de nos événements sont en participation libre : votre soutien permet de les faire vivre.",
                'body' => "*Texte à compléter : adhésion, dons, mécénat, bénévolat.*",
            ],
            'press' => [
                'title' => 'Presse',
                'body' => "Pour toute demande de la presse (visuels, interviews, dossier de presse), écrivez-nous à [abside.association@gmail.com](mailto:abside.association@gmail.com) ou appelez le 06 14 47 30 42.",
            ],
        ];
    }

    private function legal(): array
    {
        return [
            'mentions' => <<<'MD'
## Éditeur du site

L'Abside, centre culturel et touristique catholique des Alpes-Maritimes (CCTC AM)
Association — SIREN 992 749 168
23 avenue Sévigné, 06100 Nice

E-mail : [abside.association@gmail.com](mailto:abside.association@gmail.com) · Téléphone : 06 14 47 30 42

Directeur de la publication : *à compléter*

## Hébergement

o2switch — SARL au capital de 100 000 €
222-224 Boulevard Gustave Flaubert, 63000 Clermont-Ferrand, France
[www.o2switch.fr](https://www.o2switch.fr)

## Propriété intellectuelle

Les textes, photographies, affiches et visuels de ce site sont la propriété de L'Abside ou de leurs auteurs. Toute reproduction sans autorisation est interdite.

## Données personnelles

Voir notre [politique de confidentialité](/confidentialite).
MD,
            'privacy' => <<<'MD'
L'Abside est responsable des données collectées sur ce site. Elles ne sont jamais vendues ni cédées à des tiers.

## Inscription à un événement

Votre nom, votre prénom, votre adresse e-mail, votre téléphone et la date choisie sont enregistrés pour organiser l'événement et vous prévenir en cas de changement. Ils sont conservés trois ans après votre dernière participation, puis supprimés automatiquement.

## Formulaire de contact

Votre nom, votre adresse e-mail et votre message nous sont transmis par e-mail afin de vous répondre. Ils ne sont pas conservés sur le site.

## Lettre d'information

Si vous vous y inscrivez, votre prénom, votre nom et votre adresse e-mail sont enregistrés chez notre prestataire d'e-mailing **Brevo** (Sendinblue SAS, France) pour vous envoyer nos informations. Chaque lettre contient un lien de désinscription.

## Vos droits

Conformément au RGPD, vous disposez d'un droit d'accès, de rectification, d'opposition et de suppression de vos données. Écrivez-nous à [abside.association@gmail.com](mailto:abside.association@gmail.com). Vous pouvez aussi adresser une réclamation à la [CNIL](https://www.cnil.fr).
MD,
        ];
    }
}
