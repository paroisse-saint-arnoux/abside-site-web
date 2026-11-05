<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enregistre une inscription (formulaire du site ou saisie dans l'admin) :
 * le contact est créé ou mis à jour d'après son e-mail, une seule inscription par contact et par séance, jauge respectée.
 */
class Booking
{
    /**
     * @param  array{first_name: string, last_name: string, email: string, phone?: ?string, seats: int, newsletter?: bool}  $data
     *
     * @throws ValidationException si la jauge est dépassée (clé « seats »)
     */
    public static function book(Event $event, EventDate $date, array $data, bool $checkCapacity = true): Registration
    {
        // Verrou sur l'événement : deux inscriptions simultanées ne peuvent pas dépasser la jauge.
        return DB::transaction(function () use ($event, $date, $data, $checkCapacity) {
            Event::whereKey($event->id)->lockForUpdate()->first();

            $contact = Contact::firstOrNew(['email' => mb_strtolower(trim($data['email']))]);
            $contact->fill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => ($data['phone'] ?? null) ?: $contact->phone,
            ]);
            if (! empty($data['newsletter'])) {
                $contact->newsletter_at ??= now();
            }
            $contact->save();

            $existing = Registration::where('event_date_id', $date->id)->where('contact_id', $contact->id)->first();
            $remaining = $event->remainingSeats([$date])[$date->id];
            $left = $remaining === null ? null : $remaining + ($existing?->seats ?? 0);
            if ($checkCapacity && $left !== null && $data['seats'] > $left) {
                throw ValidationException::withMessages(['seats' => $left > 0
                    ? "Il ne reste que $left place".($left > 1 ? 's' : '').' pour cette date.'
                    : 'Désolé, cette date est complète.']);
            }

            $registration = $existing ?? new Registration(['event_id' => $event->id, 'event_date_id' => $date->id, 'contact_id' => $contact->id]);
            $registration->fill(['starts_at' => $date->starts_at, 'seats' => $data['seats']])->save();

            return $registration;
        });
    }
}
