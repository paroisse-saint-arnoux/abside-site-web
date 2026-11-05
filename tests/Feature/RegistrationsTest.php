<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Mail\RegistrationAlert;
use App\Mail\RegistrationCancelled;
use App\Mail\RegistrationConfirmation;
use App\Mail\RegistrationReminder;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Place;
use App\Models\Registration;
use App\Models\User;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
        $this->travelTo('2026-11-04 10:00');
    }

    private function event(array $attrs = [], array $dates = ['2026-12-12 20:30']): Event
    {
        $place = Place::create(['name' => 'Église Saint-Étienne', 'address' => '32 rue Vernier', 'postal_code' => '06000', 'city' => 'Nice', 'latitude' => 43.7, 'longitude' => 7.25]);
        $event = Event::create(['title' => 'Au nom de la mère', 'type' => 'theatre', 'is_published' => true, 'place_id' => $place->id, ...$attrs]);
        foreach ($dates as $d) {
            $event->dates()->create(['starts_at' => $d]);
        }

        return $event->load('dates');
    }

    /** Envoi du formulaire, sans être bloqué par l'anti-spam (horodatage de plus de 3 s). */
    private function register(Event $event, array $data = [])
    {
        $t = SpamGuard::timestamp();
        $this->travel(5)->seconds();

        return $this->post(route('registrations.store', $event->slug), [
            '_t' => $t, 'event_date_id' => $event->dates->first()->id,
            'first_name' => 'Marie', 'last_name' => 'Durand', 'email' => 'Marie.Durand@example.org', 'phone' => '06 00 00 00 00', 'seats' => 2,
            ...$data,
        ]);
    }

    public function test_visitor_registers_and_gets_confirmation_with_calendar_links(): void
    {
        $event = $this->event();
        $this->get($event->url())->assertSee('Je m\'inscris', false)->assertSee('Nombre de places');

        $this->register($event)->assertRedirect($event->url().'#inscription');

        $registration = Registration::with('contact')->sole();
        $this->assertSame('marie.durand@example.org', $registration->contact->email);
        $this->assertSame(2, $registration->seats);
        $this->assertNull($registration->contact->newsletter_at);

        Mail::assertSent(RegistrationConfirmation::class, fn ($m) => $m->hasTo('marie.durand@example.org'));
        Mail::assertSent(RegistrationAlert::class, fn ($m) => $m->hasTo('abside.association@gmail.com') && $m->hasReplyTo('marie.durand@example.org'));

        // Après redirection : confirmation et « Ajouter à mon agenda ».
        $this->withSession(['registration' => $registration->token])->get($event->url())
            ->assertSee('C\'est noté, Marie', false)->assertSee($registration->icsUrl())->assertSee('calendar.google.com', false);

        $this->get($registration->icsUrl())->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('DTSTART:20261212T193000Z')->assertSee('SUMMARY:Au nom de la mère')->assertSee('LOCATION:Église Saint-Étienne\, 32 rue Vernier');
    }

    public function test_emails_render_with_the_session_details(): void
    {
        $event = $this->event(['capacity' => 10]);
        $this->register($event);
        $r = Registration::with(['event.place', 'eventDate', 'contact'])->sole();

        $this->assertStringContainsString('Samedi 12 décembre 2026, 20h30', (new RegistrationConfirmation($r))->render());
        $this->assertStringContainsString($r->cancelUrl(), (new RegistrationReminder($r))->render());
        $this->assertStringContainsString('8 restantes', (new RegistrationAlert($r))->render());
        $this->assertStringContainsString('Marie Durand', (new RegistrationCancelled($r))->render());
    }

    public function test_same_email_twice_updates_one_contact_and_one_registration(): void
    {
        $event = $this->event();
        $this->register($event);
        $this->register($event, ['email' => 'marie.durand@example.org', 'seats' => 3, 'newsletter' => '1']);

        $this->assertSame(1, Contact::count());
        $this->assertSame(3, Registration::sole()->seats);
        $this->assertNotNull(Contact::sole()->newsletter_at);
    }

    public function test_capacity_is_per_date_and_shows_full(): void
    {
        $event = $this->event(['capacity' => 3, 'registration' => 'required'], ['2026-12-12 20:30', '2026-12-13 16:00']);
        $this->register($event, ['seats' => 2]);

        $this->register($event, ['email' => 'paul@example.org', 'seats' => 2])->assertSessionHasErrors(['seats' => 'Il ne reste que 1 place pour cette date.']);
        $this->register($event, ['email' => 'paul@example.org', 'seats' => 1])->assertSessionHasNoErrors();
        $this->assertSame(2, Registration::count());

        // Première date complète, la seconde reste ouverte.
        $this->flushSession();
        $this->get($event->url())->assertSee('Inscription obligatoire', false)->assertSee('<em>Complet</em>', false)->assertSee('S\'inscrire à l\'événement', false);

        $this->register($event, ['email' => 'jean@example.org', 'seats' => 3, 'event_date_id' => $event->dates[1]->id])->assertSessionHasNoErrors();
        $this->flushSession();
        $this->get($event->url())->assertSee('class="band-full"', false)->assertDontSee('Je m\'inscris', false);
    }

    public function test_events_without_registration_have_no_form(): void
    {
        $event = $this->event(['registration' => 'none']);

        $this->get($event->url())->assertDontSee('id="inscription"', false);
        $this->register($event)->assertNotFound();
    }

    public function test_participant_can_cancel_from_the_email_link(): void
    {
        $event = $this->event();
        $this->register($event);
        $registration = Registration::sole();

        $this->get($registration->cancelUrl())->assertOk()->assertSee('Garder ma place');
        $this->assertSame(1, Registration::count());

        $this->post(route('registrations.destroy', $registration->token))->assertRedirect($registration->cancelUrl());
        $this->assertSame(0, Registration::count());
        Mail::assertSent(RegistrationCancelled::class, fn ($m) => $m->hasTo('abside.association@gmail.com'));
        $this->get($registration->cancelUrl())->assertOk()->assertSee('est annulée', false);
        $this->get($registration->cancelUrl())->assertOk()->assertSee('n\'existe plus', false);
    }

    public function test_reminders_are_sent_once_the_day_before(): void
    {
        $event = $this->event([], ['2026-11-05 20:30', '2026-11-06 20:30']);
        $this->register($event);
        $this->register($event, ['email' => 'paul@example.org', 'event_date_id' => $event->dates[1]->id]);

        $this->artisan('inscriptions:rappels')->expectsOutput('1 rappel(s) envoyé(s).');
        $this->artisan('inscriptions:rappels')->expectsOutput('0 rappel(s) envoyé(s).');
        Mail::assertSent(RegistrationReminder::class, 1);
        Mail::assertSent(RegistrationReminder::class, fn ($m) => $m->hasTo('marie.durand@example.org'));
    }

    public function test_contacts_are_purged_three_years_after_their_last_event(): void
    {
        $event = $this->event([], ['2026-11-20 20:30']);
        $this->register($event);
        $this->travelTo('2028-01-01');
        $this->artisan('contacts:purger')->expectsOutput('0 contact(s) supprimé(s).');

        $this->travelTo('2029-11-21');
        $this->artisan('contacts:purger')->expectsOutput('1 contact(s) supprimé(s).');
        $this->assertSame(0, Contact::count());
        $this->assertSame(0, Registration::count());
    }

    public function test_recurring_dates_keep_their_registrations_when_the_event_is_saved(): void
    {
        $event = $this->event(['type' => 'expo', 'schedule_mode' => 'recurring', 'recurrence' => [
            'from' => '2026-11-09', 'to' => '2026-11-16', 'weekdays' => ['1'], 'slots' => [['start' => '10:00', 'end' => '12:00']],
        ]], []);
        $first = $event->dates()->first();
        $this->register($event->load('dates'));

        $event->update(['recurrence' => [...$event->recurrence, 'to' => '2026-11-23']]);
        $this->assertSame(3, $event->dates()->count());
        $this->assertSame($first->id, Registration::sole()->event_date_id);
    }

    public function test_admin_lists_adds_and_exports_registrations(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $event = $this->event(['capacity' => 2]);
        $this->register($event, ['seats' => 1]);

        Livewire::test(RegistrationsRelationManager::class, ['ownerRecord' => $event, 'pageClass' => EditEvent::class])
            ->assertOk()->assertSee('Marie Durand')
            ->callTableAction('add', data: ['event_date_id' => $event->dates->first()->id, 'first_name' => 'Paul', 'last_name' => 'Martin', 'email' => 'paul@example.org', 'seats' => 1, 'ignore_capacity' => false])
            ->assertHasNoTableActionErrors()
            ->callTableAction('add', data: ['event_date_id' => $event->dates->first()->id, 'first_name' => 'Jean', 'last_name' => 'Roux', 'email' => 'jean@example.org', 'seats' => 1, 'ignore_capacity' => false])
            ->assertNotified('Désolé, cette date est complète.');
        $this->assertSame(2, Registration::count());

        $response = Livewire::test(RegistrationsRelationManager::class, ['ownerRecord' => $event, 'pageClass' => EditEvent::class])
            ->callTableAction('export');
        $response->assertFileDownloaded('inscrits-au-nom-de-la-mere.xlsx');

        Livewire::test(ListContacts::class)->assertOk()->assertSee('Paul Martin')->assertSee('Marie Durand')
            ->callTableAction('export')->assertFileDownloaded('contacts-abside-2026-11-04.xlsx');
    }
}
