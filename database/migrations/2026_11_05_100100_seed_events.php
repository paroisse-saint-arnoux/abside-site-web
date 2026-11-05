<?php

use Database\Seeders\EventsSeeder;
use Illuminate\Database\Migrations\Migration;

/*
 * Saison 2026-2027 reprise des affiches (lieux, mots clés, événements, page « Nuit des églises »).
 * Posée par une migration, comme le reste du contenu de départ : elle ne s'exécute qu'une fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        (new EventsSeeder)->run();
    }
};
