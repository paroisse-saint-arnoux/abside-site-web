<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ManageHome;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use App\Support\Images;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    }

    private function upload(string $dir = 'evenements'): string
    {
        return Images::store(UploadedFile::fake()->image('Photo église.jpg', 1600, 1000), $dir);
    }

    public function test_upload_keeps_the_original_next_to_the_webp_copy(): void
    {
        $path = $this->upload();

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertNotNull($source = Images::sourcePath($path));
        $this->assertStringEndsWith('.jpg', $source);
        $this->assertSame(1920, Images::meta($path)['max_width']);
    }

    public function test_crop_and_rotate_start_from_the_original_and_are_remembered(): void
    {
        $path = $this->upload();

        $new = Images::edit($path, ['x' => 0, 'y' => 0, 'width' => 1000, 'height' => 1600, 'rotate' => 90, 'scaleX' => 1, 'scaleY' => 1]);
        $this->assertNotSame($path, $new);
        [$w, $h] = getimagesize(Storage::disk('public')->path($new));
        $this->assertSame([1000, 1600], [$w, $h]); // portrait après un quart de tour
        $this->assertSame(90, Images::meta($new)['edit']['rotate']);

        // Seconde retouche : de nouveau à partir de l'original 1600 × 1000, pas de la copie recadrée.
        $again = Images::edit($new, ['x' => 100, 'y' => 100, 'width' => 1200, 'height' => 800, 'rotate' => 0]);
        $this->assertSame([1200, 800], array_slice(getimagesize(Storage::disk('public')->path($again)), 0, 2));
    }

    public function test_replaced_or_removed_images_are_deleted_with_their_original_unless_still_used(): void
    {
        $old = $this->upload();
        $poster = $this->upload('evenements/affiches');
        $event = Event::create(['title' => 'Concert', 'type' => 'concert', 'image' => $old, 'poster' => $poster]);
        $copy = Event::create(['title' => 'Concert (copie)', 'type' => 'concert', 'poster' => $poster]);
        $source = Images::sourcePath($old);

        $event->update(['image' => $this->upload(), 'poster' => null]);

        Storage::disk('public')->assertMissing($old);
        Storage::disk('local')->assertMissing($source);
        Storage::disk('public')->assertExists($poster); // encore utilisée par la copie

        $copy->delete();
        Storage::disk('public')->assertMissing($poster);
    }

    public function test_settings_images_are_cleaned_up_when_replaced(): void
    {
        $old = $this->upload('accueil');
        Setting::put('home', ['hero' => ['title' => 'Accueil', 'image' => $old]]);
        Setting::put('home', ['hero' => ['title' => 'Accueil', 'image' => null]]);

        Storage::disk('public')->assertMissing($old);
    }

    public function test_image_fields_offer_drop_replace_and_crop(): void
    {
        $event = Event::create(['title' => 'Concert', 'type' => 'concert', 'image' => $this->upload()]);

        Livewire::test(EditEvent::class, ['record' => $event->id])->assertOk()
            ->assertSeeHtml('data-image-upload')->assertSee('Recadrer / pivoter');
    }

    public function test_home_blocks_accept_a_pasted_search_url_and_show_a_preview(): void
    {
        $this->assertSame('/agenda?type=theatre&mot-cle=art-foi', ManageHome::normalizeUrl('https://abside-culture.fr/agenda?type=theatre&mot-cle=art-foi&nb=60#resultats'));
        $this->assertSame('/agenda?type=concert', ManageHome::normalizeUrl('http://localhost:8090/agenda/?type=concert&utm_source=x'));
        $this->assertSame('/mot-cle/nuit-des-eglises', ManageHome::normalizeUrl('https://abside.saint-arnoux.fr/mot-cle/nuit-des-eglises?x=1'));
        $this->assertSame('https://www.helloasso.com/associations/abside', ManageHome::normalizeUrl('https://www.helloasso.com/associations/abside'));

        $doors = collect(range(1, 4))->map(fn ($i) => ['title' => "Bloc $i", 'subtitle' => null, 'color' => '#1F4E8C', 'url' => '/agenda'])->all();
        Setting::put('home', ['hero' => ['title' => 'Patrimoine'], 'doors' => $doors, 'about' => ['title' => 'Qui']]);

        $page = Livewire::test(ManageHome::class)->assertOk()->assertSee('Aperçu')->assertSee('Théâtre');
        $first = array_key_first($page->get('data.home.doors'));
        $page->set("data.home.doors.{$first}.url", 'https://abside-culture.fr/agenda?type=expo&nb=90')
            ->assertSet("data.home.doors.{$first}.url", '/agenda?type=expo')
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('/agenda?type=expo', Setting::get('home.doors')[0]['url']);
    }
}
