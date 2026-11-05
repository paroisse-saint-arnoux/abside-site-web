<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alerte envoyée à L'Abside pour chaque inscription (réponse directe au participant). */
class RegistrationAlert extends Mailable
{
    public function __construct(public Registration $registration, public bool $updated = false) {}

    public function envelope(): Envelope
    {
        $c = $this->registration->contact;

        return new Envelope(
            replyTo: [new Address($c->email, $c->fullName())],
            subject: ($this->updated ? 'Inscription modifiée' : 'Nouvelle inscription').' : '.$this->registration->event->title.' ('.$this->registration->starts_at->format('d/m').')',
        );
    }

    public function content(): Content
    {
        $r = $this->registration;
        $remaining = $r->event->capacity && $r->eventDate ? $r->event->remainingSeats([$r->eventDate])[$r->eventDate->id] : null;
        $total = $r->eventDate ? $r->eventDate->registrations()->sum('seats') : null;

        return new Content(markdown: 'mail.registration.alert', with: [
            'event' => $r->event, 'date' => $r->session(), 'contact' => $r->contact,
            'remaining' => $remaining, 'total' => $total,
        ]);
    }
}
