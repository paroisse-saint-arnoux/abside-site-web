<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Keyword;
use App\Models\Place;
use App\Models\Setting;
use App\Support\Images;
use Illuminate\Database\Seeder;

/**
 * Saison 2026-2027, d'après les affiches fournies par l'association (dossier « Assets et photos/Affiches »).
 * Idempotent : un événement, un lieu ou un mot clé déjà présent n'est pas recréé.
 */
class EventsSeeder extends Seeder
{
    private string $assets = __DIR__.'/assets/evenements';

    public function run(): void
    {
        $places = $this->places();
        $kw = $this->keywords();

        $theatre = ['partners' => [['path' => $this->img('partenaires-theatre', 'evenements/partenaires', 1200), 'alt' => 'Paroisse Saint-Jérôme, diocèse de Nice, La Procure, Accrorgue, RCF Notre-Dame']]];
        $christel = [['key' => 'Intervenante', 'value' => 'Christel Naujoks, PhD, déléguée à la Culture du diocèse de Nice']];

        $this->event('au-nom-de-la-mere', [
            'title' => 'Au nom de la mère', 'type' => 'theatre', 'place_id' => $places['etienne']->id,
            'intro' => "Erri De Luca s'empare de l'histoire la plus connue de l'humanité, la Nativité, et l'articule autour de la figure de Marie.",
            'body' => "Ou plutôt de Miriàm, une simple jeune femme juive, fiancée à Iosef quand elle tombe enceinte. « *Au nom de la mère* met en scène une Marie inouïe de candeur et de force », fait observer Francesco Agnello. « Cette force lui permet de convaincre Joseph de les aimer, elle et son enfant, malgré une faute qui devrait l'envoyer à la lapidation et à la mort ».\n\nSous la plume du romancier italien, l'histoire de la Nativité trouve un ancrage nouveau dans le contexte hébraïque, et se fait éloge d'un corps et d'une âme, ceux d'une mère.",
            'specs' => [['key' => "D'après", 'value' => 'Erri De Luca'], ['key' => 'Mise en scène et musique', 'value' => 'Francesco Agnello'], ['key' => 'Avec', 'value' => "Gérard Rouzier\nJohanna Berrebi"]],
            'image' => 'au-nom-de-la-mere', 'image_alt' => "Les comédiens d'Au nom de la mère dans une église", 'poster' => 'affiche-au-nom-de-la-mere',
            'is_featured' => true, ...$theatre,
        ], [['2026-12-12 20:30']], [$kw['theatre']]);

        $this->event('pierre-et-mohamed', [
            'title' => 'Pierre & Mohamed', 'type' => 'theatre', 'place_id' => $places['etienne']->id,
            'intro' => "Le Théâtre de l'Abside présente Pierre & Mohamed, d'Adrien Candiard.",
            'specs' => [['key' => 'Texte', 'value' => 'Adrien Candiard'], ['key' => 'Mise en scène et musique', 'value' => 'Francesco Agnello'], ['key' => 'Avec', 'value' => 'Raphaël Joly']],
            'image' => 'pierre-et-mohamed', 'image_alt' => 'Le comédien de Pierre & Mohamed sur scène', 'poster' => 'affiche-pierre-et-mohamed',
            'is_featured' => true, ...$theatre,
        ], [['2027-02-06 20:30']], [$kw['theatre']]);

        $this->event('reste-avec-nous', [
            'title' => 'Reste avec nous', 'type' => 'theatre', 'place_id' => $places['etienne']->id,
            'intro' => "Les pèlerins d'Emmaüs : une lecture spectacle d'après le texte d'Henri Guillemin.",
            'specs' => [['key' => 'Texte', 'value' => 'Henri Guillemin'], ['key' => 'Adaptation théâtrale', 'value' => 'Benoît Allemane'], ['key' => 'Lecture spectacle avec', 'value' => "Serge Barbuscia\nPatrick Courtois"]],
            'image' => 'reste-avec-nous', 'image_alt' => "Les pieds d'un pèlerin sur un chemin de pierre", 'poster' => 'affiche-reste-avec-nous',
            'is_spotlight' => true, ...$theatre,
        ], [['2027-04-03 20:30']], [$kw['theatre'], $kw['lecture']]);

        $this->event('conferences-art-et-foi', [
            'title' => 'Conférences Art & Foi', 'type' => 'conference', 'place_id' => $places['maison']->id,
            'intro' => "D'octobre 2026 à mai 2027, une conférence par mois pour découvrir la profondeur spirituelle des chefs-d'œuvre de la peinture religieuse.",
            'body' => "Nos conférences visent à transmettre la beauté de l'art par une compréhension intégrale de l'œuvre : histoire, art et spiritualité sont ainsi abordés en miroir. Les conférences sont gratuites et ouvertes à tous.",
            'specs' => $christel,
            'image' => 'transfiguration', 'image_alt' => 'La Transfiguration de Raphaël (détail)', 'poster' => 'affiche-cycle-art-et-foi',
        ], [['2026-10-09 14:30'], ['2026-11-06 14:30', 'La Transfiguration par Raphaël'], ['2026-12-04 14:30'], ['2027-01-08 14:30'], ['2027-02-12 14:30'], ['2027-03-19 14:30'], ['2027-04-16 14:30'], ['2027-05-14 14:30']], [$kw['artfoi']]);

        $this->event('architecture-et-liturgie', [
            'title' => 'Architecture et liturgie', 'type' => 'conference', 'place_id' => $places['rouret']->id,
            'intro' => "Le sens des églises à la lumière de l'histoire et de la liturgie.",
            'specs' => $christel,
            'image' => 'architecture-liturgie', 'image_alt' => "Le chœur d'une église", 'poster' => 'affiche-architecture-liturgie',
        ], [['2026-11-24 19:30']], [$kw['artfoi']]);

        $this->event('la-nativite-ce-que-la-peinture-nous-dit-de-dieu', [
            'title' => 'La Nativité : ce que la peinture nous dit de Dieu', 'type' => 'conference', 'place_id' => $places['rouret']->id,
            'intro' => "Parlons d'Art & Foi : la Nativité dans la peinture.",
            'specs' => $christel,
            'image' => 'nativite', 'image_alt' => 'Une Nativité peinte (détail)', 'poster' => 'affiche-nativite',
        ], [['2026-12-01 19:30']], [$kw['artfoi']]);

        $this->event('visite-de-la-chapelle-matisse', [
            'title' => 'Visite de la chapelle Matisse', 'type' => 'visite', 'place_id' => $places['vence']->id,
            'intro' => 'Une sortie Art & Foi pour découvrir la chapelle décorée par Henri Matisse à Vence.',
            'body' => "Rendez-vous devant la chapelle.\n\n- **Parking** sur l'avenue Matisse ou à Vence\n- **Bus 9** : de Nice Grands Arénas à Vence",
            'price_text' => 'Entrée chapelle et musée : 7 € par personne', 'registration' => 'required',
            'image' => 'chapelle-matisse', 'image_alt' => 'Les vitraux de la chapelle Matisse', 'poster' => 'affiche-chapelle-matisse',
            'is_featured' => true,
        ], [['2026-11-05 14:00']], [$kw['artfoi']]);

        $this->nuitDesEglises($kw['nuit']);
    }

    /** @return array<string, Place> */
    private function places(): array
    {
        $rows = [
            'etienne' => ['name' => 'Église Saint-Étienne', 'address' => '32 rue Vernier', 'postal_code' => '06000', 'city' => 'Nice', 'latitude' => 43.707075, 'longitude' => 7.258923],
            'maison' => ['name' => 'Maison diocésaine', 'address' => '29 boulevard Franck Pilatte', 'postal_code' => '06300', 'city' => 'Nice', 'latitude' => 43.691554, 'longitude' => 7.292189],
            'rouret' => ['name' => 'Centre Saint-Joseph', 'address' => '1104 route de Nice', 'postal_code' => '06650', 'city' => 'Le Rouret', 'latitude' => 43.677004, 'longitude' => 7.017481],
            'vence' => ['name' => 'Chapelle Matisse', 'address' => '466 avenue Henri Matisse', 'postal_code' => '06140', 'city' => 'Vence', 'latitude' => 43.72752, 'longitude' => 7.112874],
        ];

        return collect($rows)->map(fn ($r) => Place::firstOrCreate(['name' => $r['name'], 'city' => $r['city']], $r))->all();
    }

    /** @return array<string, Keyword> */
    private function keywords(): array
    {
        return collect([
            'artfoi' => 'Art & Foi',
            'theatre' => "Théâtre de l'Abside",
            'lecture' => 'Lecture spectacle',
            'nuit' => 'Nuit des églises',
        ])->map(fn ($name) => Keyword::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($name)], ['name' => $name]))->all();
    }

    private function event(string $slug, array $data, array $dates, array $keywords): void
    {
        if (Event::where('slug', $slug)->exists()) {
            return;
        }

        foreach (['image' => 'evenements', 'poster' => 'evenements/affiches'] as $field => $dir) {
            if (isset($data[$field])) {
                $data[$field] = $this->img($data[$field], $dir, $field === 'poster' ? 1600 : Images::MAX_WIDTH);
            }
        }

        $event = Event::create([...$data, 'slug' => $slug, 'is_published' => true]);
        foreach ($dates as $date) {
            $event->dates()->create(['starts_at' => $date[0], 'label' => $date[1] ?? null]);
        }
        $event->keywords()->sync(collect($keywords)->pluck('id'));
    }

    private function nuitDesEglises(Keyword $keyword): void
    {
        if (! $keyword->has_page) {
            $keyword->update([
                'has_page' => true,
                'intro' => 'Une manifestation cultuelle et culturelle inscrite dans le paysage estival des diocèses depuis 2011.',
                'body' => "La Nuit des églises, initiée par l'Église de France, bénéficie d'une reconnaissance à l'échelle nationale : plusieurs centaines d'églises y participent chaque année.",
                'where_label' => 'Églises des Alpes-Maritimes',
                'image' => $this->img('nuit-img-7899', 'mots-cles'),
                'image_alt' => "Vitraux projetés sur la façade d'une église, la nuit, devant le public",
                'gallery' => [
                    ['path' => $this->img('nuit-img-7427', 'mots-cles/galerie'), 'alt' => "Bougies sur les marches d'un parvis"],
                    ['path' => $this->img('nuit-img-7447', 'mots-cles/galerie'), 'alt' => "Bougies le long des marches d'une église"],
                    ['path' => $this->img('nuit-img-7492', 'mots-cles/galerie'), 'alt' => 'Le public devant une église illuminée, la nuit'],
                ],
            ]);
        }

        $home = Setting::get('home', []);
        if (! isset($home['highlight'])) {
            $home['highlight'] = [
                'enabled' => true,
                'title' => 'Nuit des églises',
                'text' => "Initiée par l'Église de France, une manifestation cultuelle et culturelle inscrite dans le paysage estival des diocèses depuis 2011, à laquelle participent chaque année plusieurs centaines d'églises.",
                'label' => null,
                'url' => '/mot-cle/nuit-des-eglises',
                'button' => 'Découvrir',
                'image' => $keyword->fresh()->image,
                'image_alt' => "Vitraux projetés sur la façade d'une église, la nuit",
            ];
            Setting::put('home', $home);
        }
    }

    private function img(string $name, string $dir, int $maxWidth = Images::MAX_WIDTH): string
    {
        return Images::store($this->assets."/{$name}.jpg", $dir, $maxWidth);
    }
}
