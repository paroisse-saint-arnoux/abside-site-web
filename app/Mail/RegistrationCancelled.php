<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alerte envoyée à L'Abside quand un participant annule son inscription. */
class RegistrationCancelled extends Mailable
{
    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        $c = $this->registration->contact;

        return new Envelope(
            replyTo: [new Address($c->email, $c->fullName())],
            subject: 'Inscription annulée : '.$this->registration->event->title.' ('.$this->registration->starts_at->format('d/m').')',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.registration.cancelled', with: [
            'event' => $this->registration->event, 'date' => $this->registration->session(), 'contact' => $this->registration->contact,
        ]);
    }
}
