<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lieux (églises, chapelles, salles) : réutilisés d'un événement à l'autre, placés sur la carte de l'agenda.
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        // Mots clés (« Art & Foi », « Nuit des églises »…). Certains ont leur propre page (même gabarit qu'un événement).
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('has_page')->default(false);
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->string('image')->nullable();
            $table->string('image_alt')->nullable();
            // [{path, alt}]
            $table->json('gallery')->nullable();
            // Textes libres du bandeau de la page (« Dates 2027 à confirmer », « Églises des Alpes-Maritimes »).
            $table->string('when_label')->nullable();
            $table->string('where_label')->nullable();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type');
            $table->foreignId('place_id')->nullable()->constrained()->nullOnDelete();
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->string('image')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('poster')->nullable();
            // [{path, alt}] — galerie (carrousel sur mobile).
            $table->json('gallery')->nullable();
            // [{key, value}] — jusqu'à 6 caractéristiques (« Mise en scène », « Avec »…), valeur en Markdown.
            $table->json('specs')->nullable();
            // [{path, alt}] — logos des partenaires, séparés ou en bandeau, dans l'ordre d'affichage.
            $table->json('partners')->nullable();
            $table->boolean('is_free')->default(true);
            $table->string('price_text')->nullable();
            // none | optional | required ; capacity = jauge par séance (vide = illimitée).
            $table->string('registration')->default('optional');
            $table->unsignedInteger('capacity')->nullable();
            // Mise en avant sur l'accueil / exergue sur l'agenda.
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_spotlight')->default(false);
            // dates : séances saisies une à une ; recurring : horaires récurrents (expositions), séances générées.
            $table->string('schedule_mode')->default('dates');
            $table->json('recurrence')->nullable();
            $table->boolean('is_published')->default(false);
            $table->string('preview_token', 40)->unique();
            $table->timestamps();

            $table->index(['is_published', 'type']);
        });

        // Une ligne par séance : c'est ce que l'agenda affiche (un cycle ou un horaire récurrent apparaît à chaque date).
        Schema::create('event_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            // Sous-titre propre à la séance (« La Transfiguration par Raphaël »).
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index('starts_at');
        });

        Schema::create('event_keyword', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'keyword_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_keyword');
        Schema::dropIfExists('event_dates');
        Schema::dropIfExists('events');
        Schema::dropIfExists('keywords');
        Schema::dropIfExists('places');
    }
};
