<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Widgets\TopPages;
use App\Filament\Widgets\VisitsChart;
use App\Filament\Widgets\VisitsStats;
use App\Models\User;
use App\Services\Matomo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        config(['services.matomo' => ['url' => 'https://matomo.test/', 'site_id' => 4, 'token' => 'secret']]);
    }

    public function test_dashboard_shows_matomo_statistics(): void
    {
        // 60 jours : 10 visites par jour sur les 30 premiers, 15 sur les 30 derniers.
        $days = [];
        foreach (range(59, 0) as $i) {
            $days[now()->subDays($i)->toDateString()] = $i >= 30
                ? ['nb_visits' => 10, 'nb_uniq_visitors' => 8, 'nb_actions' => 30]
                : ['nb_visits' => 15, 'nb_uniq_visitors' => 12, 'nb_actions' => 40];
        }
        $days[now()->toDateString()] = []; // jour sans visite : Matomo renvoie un tableau vide

        Http::fake(fn (Request $request) => match ($request['method']) {
            'VisitsSummary.get' => Http::response($days),
            'Actions.getPageTitles' => Http::response([['label' => ' Agenda', 'url' => 'https://abside-culture.fr/agenda', 'nb_hits' => 120]]),
        });

        $this->get('/admin')->assertOk();

        Livewire::test(VisitsStats::class)->assertSee('435')->assertSee('+45 %'); // 29 × 15 = 435 contre 300
        Livewire::test(VisitsChart::class)->assertSee('Visites');
        Livewire::test(TopPages::class)->assertSee('Agenda')->assertSee('120');

        Http::assertSent(fn (Request $request) => $request['token_auth'] === 'secret' && $request['idSite'] === 4);
    }

    public function test_dashboard_explains_missing_configuration_and_errors(): void
    {
        config(['services.matomo.token' => null]);
        Livewire::test(VisitsStats::class)->assertSee('Non configurées');

        config(['services.matomo.token' => 'mauvais']);
        Http::fake(['*' => Http::response(['result' => 'error', 'message' => "requires 'view' access"])]);
        Livewire::test(VisitsStats::class)->assertSee('Indisponibles');
        $this->assertSame([], app(Matomo::class)->dailyVisits());
    }

    public function test_tracking_code_is_only_added_in_production(): void
    {
        config(['services.matomo.url' => 'https://matomo.saint-arnoux.fr/', 'services.matomo.site_id' => 4]);
        $this->get('/')->assertDontSee('matomo.js');

        $this->app['env'] = 'production';
        $this->get('/')->assertSee('matomo.saint-arnoux.fr', false)->assertSee('"4"', false)->assertSee('disableCookies');
    }
}
