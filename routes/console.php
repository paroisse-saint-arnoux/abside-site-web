<?php

use App\Mail\RegistrationReminder;
use App\Models\Contact;
use App\Models\Registration;
use App\Models\User;
use App\Support\MagicLink;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

// Développement : lien de connexion à l'admin sans passer par l'email (tests de rendu, captures d'écran).
// php artisan admin:lien [email]  (par défaut : le premier administrateur)
Artisan::command('admin:lien {email?}', function () {
    if (app()->isProduction()) {
        $this->error('Commande désactivée en production.');

        return 1;
    }

    $user = $this->argument('email')
        ? User::firstWhere('email', $this->argument('email'))
        : User::where('is_active', true)->where('role', 'admin')->orderBy('id')->first();
    if (! $user) {
        $this->error('Utilisateur introuvable.');

        return 1;
    }

    $this->line(MagicLink::url($user, now()->addMinutes(MagicLink::MINUTES)));
})->purpose('Affiche un lien de connexion à l\'admin (hors production)');

// Rappel par e-mail la veille de chaque séance (inscriptions de demain pas encore rappelées).
Artisan::command('inscriptions:rappels', function () {
    $tomorrow = now()->addDay();
    $sent = 0;
    Registration::whereNull('reminded_at')
        ->whereBetween('starts_at', [$tomorrow->copy()->startOfDay(), $tomorrow->copy()->endOfDay()])
        ->with(['event.place', 'eventDate', 'contact'])
        ->each(function (Registration $registration) use (&$sent) {
            try {
                Mail::to($registration->contact->email, $registration->contact->fullName())->send(new RegistrationReminder($registration));
                $registration->update(['reminded_at' => now()]);
                $sent++;
            } catch (Throwable $e) {
                report($e);
            }
        });
    $this->info("$sent rappel(s) envoyé(s).");
})->purpose('Envoie le rappel de la veille aux inscrits des séances de demain');

// RGPD : suppression des contacts (et de leurs inscriptions) 3 ans après leur dernière participation.
Artisan::command('contacts:purger', function () {
    $count = Contact::expired()->count();
    Contact::expired()->each(fn (Contact $c) => $c->delete());
    $this->info("$count contact(s) supprimé(s).");
})->purpose('Supprime les contacts sans participation depuis '.Contact::RETENTION_YEARS.' ans');

Schedule::command('inscriptions:rappels')->dailyAt('10:00');
Schedule::command('contacts:purger')->dailyAt('03:30');
