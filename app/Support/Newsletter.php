<?php

namespace App\Support;

use App\Models\Contact;
use App\Services\Brevo;

/**
 * Inscription à la lettre d'information : toujours enregistrée dans la base (contacts.newsletter_at),
 * puis reportée dans Brevo si possible. Les inscrits pas encore reportés sont repris par newsletter:brevo.
 */
class Newsletter
{
    public static function subscribe(string $email, string $firstName, string $lastName): Contact
    {
        $contact = Contact::firstOrNew(['email' => mb_strtolower(trim($email))]);
        $contact->fill(['first_name' => $firstName, 'last_name' => $lastName]);
        $contact->newsletter_at ??= now();
        $contact->save();

        static::sync($contact);

        return $contact;
    }

    /** Reporte le contact dans la liste Brevo ; true si c'est fait (ou déjà fait). */
    public static function sync(Contact $contact): bool
    {
        if ($contact->newsletter_at === null) {
            return false;
        }
        if ($contact->brevo_synced_at !== null) {
            return true;
        }
        if (! static::brevoConfigured()) {
            return false;
        }

        $ok = rescue(fn () => app(Brevo::class)->subscribe($contact->email, $contact->first_name, $contact->last_name), false);
        if ($ok) {
            $contact->forceFill(['brevo_synced_at' => now()])->saveQuietly();
        }

        return $ok;
    }

    public static function brevoConfigured(): bool
    {
        return filled(config('services.brevo.key')) && (bool) config('services.brevo.list_id');
    }
}
