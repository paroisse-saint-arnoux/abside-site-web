<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ManageHome;
use App\Filament\Pages\ManageSite;
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
}
