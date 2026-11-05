<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationAlert;
use App\Mail\RegistrationCancelled;
use App\Mail\RegistrationConfirmation;
use App\Models\Event;
use App\Models\Registration;
use App\Services\Brevo;
use App\Support\Booking;
use App\Support\Calendar;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function store(Request $request, string $slug, Brevo $brevo)
    {
        $event = Event::where('slug', $slug)->with(['place', 'dates'])->firstOrFail();
        abort_unless($event->is_published || $request->user()?->is_active, 404);
        abort_unless($event->acceptsRegistrations(), 404);

        try {
            return $this->register($request, $event, $brevo);
        } catch (ValidationException $e) {
            // Retour sur le formulaire (et non en haut de la page), avec les erreurs.
            throw $e->redirectTo($event->url().'#inscription');
        }
    }

    private function register(Request $request, Event $event, Brevo $brevo)
    {
        $upcoming = $event->dates->filter(fn ($d) => $d->starts_at->isFuture());
        $data = $request->validate([
            'event_date_id' => ['required', Rule::in($upcoming->pluck('id')->all())],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'seats' => ['required', 'integer', 'min:1', 'max:'.Registration::MAX_SEATS],
            'newsletter' => ['nullable', 'boolean'],
        ], [
            'event_date_id.required' => 'Choisissez une date.',
            'event_date_id.in' => 'Cette date n\'est plus disponible.',
        ]);

        if (! SpamGuard::passes($request)) {
            throw ValidationException::withMessages(['email' => 'Votre inscription n\'a pas pu être enregistrée. Merci de réessayer.']);
        }

        $date = $upcoming->firstWhere('id', (int) $data['event_date_id']);

        $registration = Booking::book($event, $date, $data);

        $registration->load(['event.place', 'eventDate', 'contact']);
        // L'inscription est enregistrée même si l'envoi d'un e-mail échoue (l'erreur est journalisée).
        rescue(fn () => Mail::to($registration->contact->email, $registration->contact->fullName())->send(new RegistrationConfirmation($registration)));
        rescue(fn () => Mail::to(config('services.contact.email'))->send(new RegistrationAlert($registration, ! $registration->wasRecentlyCreated)));

        if (! empty($data['newsletter'])) {
            $brevo->subscribe($registration->contact->email, $data['first_name'], $data['last_name']);
        }

        return redirect()->to($event->url().'#inscription')->with('registration', $registration->token);
    }

    /** Fichier .ics de la séance réservée. */
    public function ics(string $token)
    {
        $registration = Registration::where('token', $token)->with(['event.place', 'eventDate'])->firstOrFail();

        return response(Calendar::ics($registration->event, $registration->session(), 'inscription-'.$registration->id), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$registration->event->slug.'.ics"',
        ]);
    }

    /** Page d'annulation (lien de l'e-mail de confirmation) : un bouton à confirmer, pour éviter les annulations par les robots de messagerie. */
    public function cancel(string $token)
    {
        $registration = Registration::where('token', $token)->with(['event.place', 'eventDate', 'contact'])->first();

        return view('registrations.cancel', ['registration' => $registration]);
    }

    public function destroy(string $token)
    {
        $registration = Registration::where('token', $token)->with(['event.place', 'eventDate', 'contact'])->firstOrFail();
        if ($registration->starts_at->isFuture()) {
            Mail::to(config('services.contact.email'))->send(new RegistrationCancelled($registration));
            $registration->delete();
        }

        return redirect()->route('registrations.cancel', $token)->with('cancelled', $registration->event->title);
    }
}
