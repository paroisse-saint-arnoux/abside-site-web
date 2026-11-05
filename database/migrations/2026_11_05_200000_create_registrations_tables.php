<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscriptions aux événements et carnet de contacts (un contact par adresse e-mail).
 * RGPD : un contact et ses inscriptions sont supprimés 3 ans après sa dernière participation (contacts:purger).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('phone', 30)->nullable();
            $table->timestamp('newsletter_at')->nullable();
            $table->timestamps();
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // La séance peut disparaître (dates modifiées) : on garde sa date et son heure dans starts_at.
            $table->foreignId('event_date_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->unsignedSmallInteger('seats')->default(1);
            $table->string('token', 40)->unique();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
            $table->index(['event_date_id', 'contact_id']);
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('contacts');
    }
};
