<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Newsletter : les inscrits sont enregistrés dans contacts (newsletter_at), puis reportés dans Brevo.
 * brevo_synced_at reste vide tant que le report n'a pas réussi (Brevo non configuré ou en erreur) : newsletter:brevo les reprend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->timestamp('brevo_synced_at')->nullable()->after('newsletter_at');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('brevo_synced_at');
        });
    }
};
