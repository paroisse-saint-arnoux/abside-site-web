<?php

namespace Tests\Feature;

use App\Mail\ContactMail;
use App\Models\Contact;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormsTest extends TestCase
{
    use RefreshDatabase;

    private function oldTimestamp(): string
    {
        $this->travel(-10)->seconds();
        $t = SpamGuard::timestamp();
        $this->travelBack();

        return $t;
    }

    public function test_newsletter_subscribes_contact_to_brevo_list(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);

        $this->postJson('/newsletter', [
            'first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'Marie@Example.com', '_t' => $this->oldTimestamp(),
        ])->assertOk()->assertJsonPath('ok', true);

        Http::assertSent(fn ($request) => $request['email'] === 'marie@example.com'
            && $request['attributes'] === ['PRENOM' => 'Marie', 'NOM' => 'Martin']
            && $request['listIds'] === [2]
            && $request->hasHeader('api-key', 'test-key'));

        $contact = Contact::sole();
        $this->assertSame('marie@example.com', $contact->email);
        $this->assertNotNull($contact->newsletter_at);
        $this->assertNotNull($contact->brevo_synced_at);
    }

    public function test_newsletter_is_saved_in_database_without_brevo_then_synced_later(): void
    {
        config(['services.brevo.key' => null]);
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);

        $this->postJson('/newsletter', [
            'first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'marie@example.com', '_t' => $this->oldTimestamp(),
        ])->assertOk()->assertJsonPath('ok', true);

        Http::assertNothingSent();
        $contact = Contact::sole();
        $this->assertNotNull($contact->newsletter_at);
        $this->assertNull($contact->brevo_synced_at);
        $this->artisan('newsletter:brevo')->expectsOutputToContain('1 inscrit(s) en attente');

        // Brevo configuré : la commande reporte les inscrits en attente.
        config(['services.brevo.key' => 'test-key']);
        $this->artisan('newsletter:brevo')->expectsOutput('1 inscrit(s) reporté(s) dans Brevo.');
        Http::assertSentCount(1);
        $this->assertNotNull($contact->fresh()->brevo_synced_at);
    }

    public function test_newsletter_is_saved_even_if_brevo_fails(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'error'], 500)]);

        $this->postJson('/newsletter', [
            'first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'marie@example.com', '_t' => $this->oldTimestamp(),
        ])->assertOk();

        $this->assertNull(Contact::sole()->brevo_synced_at);
        $this->assertSame(1, Contact::awaitingBrevo()->count());
    }

    public function test_newsletter_subscribers_are_not_purged(): void
    {
        $this->travelTo('2026-11-01');
        Contact::create(['email' => 'lettre@example.com', 'first_name' => 'A', 'last_name' => 'B', 'newsletter_at' => now()]);
        Contact::create(['email' => 'ancien@example.com', 'first_name' => 'C', 'last_name' => 'D']);

        $this->travelTo('2030-01-01');
        $this->artisan('contacts:purger')->expectsOutput('1 contact(s) supprimé(s).');
        $this->assertSame(['lettre@example.com'], Contact::pluck('email')->all());
    }

    public function test_newsletter_rejects_bots(): void
    {
        Http::fake();

        // Pot de miel rempli
        $this->postJson('/newsletter', [
            'first_name' => 'Bot', 'last_name' => 'Bot', 'email' => 'bot@example.com', 'website' => 'spam', '_t' => $this->oldTimestamp(),
        ])->assertStatus(422);

        // Formulaire envoyé trop vite
        $this->postJson('/newsletter', [
            'first_name' => 'Bot', 'last_name' => 'Bot', 'email' => 'bot@example.com', '_t' => SpamGuard::timestamp(),
        ])->assertStatus(422);

        Http::assertNothingSent();
        $this->assertSame(0, Contact::count());
    }

    public function test_contact_form_sends_email_to_team(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Jean Dupont', 'email' => 'jean@example.com', 'subject' => 'Bénévolat', 'message' => 'Je souhaite aider.', '_t' => $this->oldTimestamp(),
        ])->assertRedirect('/contact')->assertSessionHas('contact-sent');

        Mail::assertSent(ContactMail::class, fn (ContactMail $mail) => $mail->hasTo(config('services.contact.email'))
            && $mail->hasReplyTo('jean@example.com'));
    }

    public function test_contact_form_blocks_honeypot(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Spam', 'website' => 'http://spam', '_t' => $this->oldTimestamp(),
        ])->assertSessionHasErrors('message');

        Mail::assertNothingSent();
    }
}
