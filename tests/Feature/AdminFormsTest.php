<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ManageHome;
use App\Filament\Pages\ManageSite;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Keywords\Pages\ManageKeywords;
use App\Filament\Resources\Places\Pages\ManagePlaces;
use App\Models\Event;
use App\Models\Place;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    }

    public function test_settings_pages_remember_saved_data_for_the_unsaved_changes_bar(): void
    {
        Setting::put('site', ['email' => 'abside.association@gmail.com', 'phone' => '06 14 47 30 42']);
        Setting::put('pages', ['about' => ['title' => 'Qui sommes-nous'], 'support' => ['title' => 'Soutenir']]);

        $page = Livewire::test(ManageSite::class);
        $hash = fn () => md5(str_replace('\\', '', json_encode($page->get('data'), JSON_UNESCAPED_UNICODE)));
        $this->assertSame($hash(), $page->get('savedDataHash'));

        $page->set('data.site.phone', '04 93 00 00 00');
        $this->assertNotSame($hash(), $page->get('savedDataHash'));

        $page->call('save')->assertHasNoFormErrors();
        $this->assertSame($hash(), $page->get('savedDataHash'));
        $this->assertSame('04 93 00 00 00', Setting::get('site.phone'));
    }

    public function test_home_page_keeps_exactly_four_blocks(): void
    {
        $doors = collect(range(1, 4))->map(fn ($i) => ['title' => "Bloc $i", 'subtitle' => null, 'color' => '#1F4E8C', 'url' => '/agenda'])->all();
        Setting::put('home', ['hero' => ['title' => 'Patrimoine'], 'doors' => $doors, 'about' => ['title' => 'Qui']]);

        $page = Livewire::test(ManageHome::class);
        $first = array_key_first($page->get('data.home.doors'));
        $page->set("data.home.doors.{$first}.title", 'Théâtre')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(4, Setting::get('home.doors'));
        $this->assertSame('Théâtre', Setting::get('home.doors')[0]['title']);
    }

    public function test_event_admin_creates_an_event_with_dates_and_lists_it(): void
    {
        $place = Place::create(['name' => 'Église Saint-Étienne', 'city' => 'Nice', 'latitude' => 43.7, 'longitude' => 7.25]);

        Livewire::test(CreateEvent::class)
            ->fillForm([
                'title' => 'Au nom de la mère', 'type' => 'theatre', 'place_id' => $place->id,
                'schedule_mode' => 'dates', 'registration' => 'optional', 'is_published' => true,
            ])
            ->set('data.dates', [['starts_at' => '2026-12-12 20:30', 'ends_at' => null, 'label' => null]])
            ->call('create')
            ->assertHasNoFormErrors();

        $event = Event::firstWhere('slug', 'au-nom-de-la-mere');
        $this->assertNotNull($event);
        $this->assertSame(1, $event->dates()->count());

        Livewire::test(EditEvent::class, ['record' => $event->id])->assertOk()->assertSchemaStateSet(['title' => 'Au nom de la mère']);
        Livewire::test(ListEvents::class)->assertOk()->assertSee('Au nom de la mère');
        Livewire::test(ManagePlaces::class)->assertOk()->assertSee('Église Saint-Étienne');
        Livewire::test(ManageKeywords::class)->assertOk();
    }
}
