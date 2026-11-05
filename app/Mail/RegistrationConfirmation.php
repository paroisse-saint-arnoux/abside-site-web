<?php

namespace App\Mail;

use App\Models\Registration;
use App\Support\Calendar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Confirmation envoyée au participant, avec le fichier .ics de la séance. */
class RegistrationConfirmation extends Mailable
{
    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address(config('services.contact.email'), "L'Abside")],
            subject: 'Inscription confirmée : '.$this->registration->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.registration.confirmation', with: [
            'event' => $this->registration->event,
            'date' => $this->registration->session(),
            'googleUrl' => Calendar::googleUrl($this->registration->event, $this->registration->session()),
        ]);
    }

    public function attachments(): array
    {
        $r = $this->registration;

        return [Attachment::fromData(fn () => Calendar::ics($r->event, $r->session(), 'inscription-'.$r->id), $r->event->slug.'.ics')->withMime('text/calendar')];
    }
}
