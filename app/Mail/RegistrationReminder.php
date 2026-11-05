<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Rappel envoyé au participant la veille de la séance. */
class RegistrationReminder extends Mailable
{
    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address(config('services.contact.email'), "L'Abside")],
            subject: 'Demain : '.$this->registration->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.registration.reminder', with: [
            'event' => $this->registration->event,
            'date' => $this->registration->session(),
        ]);
    }
}
