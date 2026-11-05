<?php

use App\Models\User;
use App\Support\MagicLink;
use Illuminate\Support\Facades\Artisan;

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
