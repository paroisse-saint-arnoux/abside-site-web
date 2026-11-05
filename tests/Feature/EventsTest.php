<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Keyword;
use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->travelTo('2026-11-04 10:00');
    }

    private function place(string $name = 'Église Saint-Étienne', string $city = 'Nice', float $lat = 43.707, float $lng = 7.259): Place
    {
        return Place::create(['name' => $name, 'city' => $city, 'latitude' => $lat, 'longitude' => $lng]);
    }

    private function event(array $attrs = [], array $dates = ['2026-12-12 20:30'], array $keywords = []): Event
    {
        $event = Event::create([
            'title' => 'Au nom de la mère', 'type' => 'theatre', 'is_published' => true,
            'place_id' => $attrs['place_id'] ?? $this->place()->id, ...$attrs,
        ]);
        foreach ($dates as $d) {
            $event->dates()->create(['starts_at' => $d]);
        }
        $event->keywords()->sync(collect($keywords)->map(fn ($k) => Keyword::firstOrCreate(['slug' => \Str::slug($k)], ['name' => $k])->id));

        return $event;
    }

    public function test_agenda_lists_one_row_per_date_and_counts_each_filter_with_the_others(): void
    {
        $nice = $this->place();
        $vence = $this->place('Chapelle Matisse', 'Vence', 43.727, 7.113);
        $this->event(['title' => 'Cycle Art & Foi', 'type' => 'conference', 'place_id' => $nice->id], ['2026-11-06 14:30', '2026-12-04 14:30', '2027-01-08 14:30'], ['Art & Foi']);
        $this->event(['title' => 'Visite Matisse', 'type' => 'visite', 'place_id' => $vence->id], ['2026-11-05 14:00'], ['Art & Foi']);
        $this->event(['title' => 'Ancienne conférence', 'type' => 'conference', 'place_id' => $nice->id], ['2026-10-01 14:30']);
        $this->event(['title' => 'Brouillon', 'is_published' => false], ['2026-11-20 20:00']);

        $this->get('/agenda')->assertOk()->assertSeeInOrder(['Visite Matisse', 'Cycle Art & Foi', 'Cycle Art & Foi', 'Cycle Art & Foi'])
            ->assertDontSee('Ancienne conférence')->assertDontSee('Brouillon')->assertSee('4 dates');

        // Filtre « Conférences » : les autres types gardent leur compteur calculé sans le filtre de type.
        $this->get('/agenda?type=conference')->assertSee('3 dates')->assertDontSee('Visite Matisse')
            ->assertSee('Visites guidées <span>1</span>', false);

        // Mois + lieu.
        $this->get('/agenda?mois=2026-12')->assertSee('1 date');
        $this->get('/agenda?lieu='.$vence->id)->assertSee('1 date')->assertSee('Visite Matisse');

        // Zone de la carte autour de Vence seulement.
        $this->get('/agenda?zone=7.0,43.70,7.2,43.75')->assertSee('1 date')->assertSee('Recherche par lieu');

        $this->get('/agenda?passes=1')->assertSee('Ancienne conférence')->assertDontSee('Visite Matisse');
    }

    public function test_agenda_answers_json_for_filters_without_reload(): void
    {
        $this->event();

        $this->getJson('/agenda?type=theatre', ['X-Agenda' => '1'])->assertOk()
            ->assertJsonStructure(['results', 'filters', 'pins' => [['name', 'lat', 'lng', 'count', 'color', 'icon']]]);
    }

    public function test_spotlight_event_is_shown_on_top_of_the_agenda(): void
    {
        $this->event(['title' => 'Reste avec nous', 'is_spotlight' => true], ['2027-04-03 20:30']);

        $this->get('/agenda')->assertSee('class="spotlight"', false)->assertSee('Reste avec nous');
        $this->get('/agenda?type=conference')->assertDontSee('class="spotlight"', false);
    }

    public function test_home_featured_events_prefer_checked_ones_then_fill_with_next_events(): void
    {
        $a = $this->event(['title' => 'Coché lointain', 'is_featured' => true], ['2027-04-03 20:30']);
        $b = $this->event(['title' => 'Prochain non coché'], ['2026-11-06 14:30']);
        $c = $this->event(['title' => 'Coché proche', 'is_featured' => true], ['2026-12-12 20:30']);
        $this->event(['title' => 'Non coché lointain'], ['2027-05-14 14:30']);
        $this->event(['title' => 'Passé coché', 'is_featured' => true], ['2026-10-01 20:30']);

        $this->assertSame([$b->id, $c->id, $a->id], Event::featuredForHome(3)->pluck('id')->all());

        // Trop de cochés : on garde ceux dont la date de fin est la plus proche.
        $this->assertSame([$c->id], Event::featuredForHome(1)->pluck('id')->all());
    }

    public function test_recurring_schedule_generates_one_date_per_slot(): void
    {
        $event = $this->event(['title' => 'Exposition', 'type' => 'expo', 'schedule_mode' => 'recurring', 'recurrence' => [
            'from' => '2026-11-02', 'to' => '2026-11-15', 'weekdays' => ['1', '4'],
            'slots' => [['start' => '10:00', 'end' => '12:00'], ['start' => '14:00', 'end' => '18:00']],
            'exceptions' => ['2026-11-12'],
        ]], []);

        // Lundis 2 et 9, jeudi 5 (le jeudi 12 est fermé) : 3 jours × 2 créneaux.
        $this->assertSame(6, $event->dates()->count());
        $this->assertSame('2026-11-02 10:00', $event->dates->first()->starts_at->format('Y-m-d H:i'));
        $this->assertSame('10h – 12h', $event->dates->first()->timeLabel());
    }

    public function test_event_page_has_share_tags_json_ld_and_hides_drafts(): void
    {
        Storage::fake('public');
        $event = $this->event(['intro' => 'La Nativité autour de Marie.', 'specs' => [['key' => 'Avec', 'value' => "Gérard Rouzier\nJohanna Berrebi"]]]);

        $this->get($event->url())->assertOk()
            ->assertSee('<meta property="og:image" content="'.$event->shareImageUrl().'"', false)
            ->assertSee('"@type":"TheaterEvent"', false)
            ->assertSee('"startDate":"2026-12-12T20:30:00+01:00"', false)
            ->assertSee('Gérard Rouzier<br', false)
            ->assertSee("S'inscrire à l'événement", false);

        $this->get($event->shareImageUrl())->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $event->update(['is_published' => false]);
        $this->get($event->url())->assertNotFound();
        $this->get($event->previewUrl())->assertOk()->assertSee('Brouillon');
    }

    public function test_keyword_without_page_redirects_to_filtered_agenda(): void
    {
        $this->event([], ['2026-12-12 20:30'], ['Art & Foi', 'Nuit des églises']);
        Keyword::where('slug', 'nuit-des-eglises')->update(['has_page' => true, 'intro' => 'Une manifestation estivale.']);

        $this->get('/mot-cle/art-foi')->assertRedirect('/agenda?mot-cle=art-foi');
        $this->get('/mot-cle/nuit-des-eglises')->assertOk()->assertSee('Une manifestation estivale.')->assertSee('Au nom de la mère');
    }

    public function test_place_coordinates_are_looked_up_from_the_address(): void
    {
        Http::fake(['api-adresse.data.gouv.fr/*' => Http::response(['features' => [
            ['geometry' => ['coordinates' => [7.258923, 43.707075]], 'properties' => ['score' => 0.98]],
        ]])]);

        $place = Place::create(['name' => 'Église Saint-Étienne', 'address' => '32 rue Vernier', 'postal_code' => '06000', 'city' => 'Nice']);

        $this->assertEqualsWithDelta(43.707075, $place->latitude, 0.00001);
        $this->assertEqualsWithDelta(7.258923, $place->longitude, 0.00001);
    }

    /** @return array<int, array> blocs JSON-LD de la page */
    private function jsonLd(string $url): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $this->get($url)->assertOk()->getContent(), $m);

        return array_map(fn ($b) => json_decode($b, true, flags: JSON_THROW_ON_ERROR), $m[1]);
    }

    public function test_every_page_describes_the_organization_page_and_breadcrumb_in_json_ld(): void
    {
        \App\Models\Setting::put('site', ['email' => 'abside.association@gmail.com', 'phone' => '06 14 47 30 42', 'address' => '23 avenue Sévigné, 06100 Nice']);
        $event = $this->event([], ['2026-12-12 20:30'], ['Nuit des églises']);
        Keyword::where('slug', 'nuit-des-eglises')->update(['has_page' => true]);

        $types = fn (array $blocks) => collect($blocks)->flatMap(fn ($b) => isset($b['@graph']) ? array_column($b['@graph'], '@type') : [$b['@type']])->all();

        foreach (['/' => 'WebPage', '/agenda' => 'CollectionPage', '/mot-cle/nuit-des-eglises' => 'CollectionPage', '/qui-sommes-nous' => 'AboutPage', '/contact' => 'ContactPage', $event->url() => 'ItemPage'] as $url => $type) {
            $found = $types($this->jsonLd($url));
            $this->assertContains('Organization', $found, $url);
            $this->assertContains($type, $found, $url);
        }

        $graph = collect($this->jsonLd($event->url()))->firstWhere('@graph')['@graph'];
        $org = collect($graph)->firstWhere('@type', 'Organization');
        $this->assertSame('+33 6 14 47 30 42', $org['telephone']);
        $this->assertSame(['@type' => 'PostalAddress', 'streetAddress' => '23 avenue Sévigné', 'postalCode' => '06100', 'addressLocality' => 'Nice', 'addressRegion' => 'Alpes-Maritimes', 'addressCountry' => 'FR'], $org['address']);
        $crumbs = collect($graph)->firstWhere('@type', 'BreadcrumbList')['itemListElement'];
        $this->assertSame(['Accueil', 'Agenda', 'Théâtre', 'Au nom de la mère'], array_column($crumbs, 'name'));

        $list = collect(collect($this->jsonLd('/agenda'))->firstWhere('@graph')['@graph'])->firstWhere('@type', 'ItemList');
        $this->assertSame($event->url(), $list['itemListElement'][0]['url']);
    }

    public function test_robots_only_allows_indexing_on_the_public_domain(): void
    {
        config(['app.url' => 'https://abside.saint-arnoux.fr']);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /')->assertDontSee('Sitemap');
        $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        config(['app.url' => 'https://abside-culture.fr']);
        $this->get('/robots.txt')->assertSee('Sitemap: https://abside-culture.fr/sitemap.xml')->assertDontSee("Disallow: /\n", false);
        $this->get('/')->assertDontSee('noindex', false)->assertSee('rel="canonical"', false);
    }

    public function test_sitemap_lists_published_events_and_keyword_pages(): void
    {
        $event = $this->event();
        $draft = $this->event(['title' => 'Brouillon', 'is_published' => false]);
        Keyword::create(['name' => 'Nuit des églises', 'has_page' => true]);

        $this->get('/sitemap.xml')->assertOk()->assertSee($event->url())->assertDontSee($draft->url())->assertSee('/mot-cle/nuit-des-eglises');
    }
}
