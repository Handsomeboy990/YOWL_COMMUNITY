<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Combien de fois ce compte s'est connecté.
 *
 * Sert au délai de grâce sur la vérification d'adresse : exiger un code dès
 * la première connexion bloque tout le monde le jour où le relais de mail
 * tombe, et personne ne découvre le site. Le compte peut donc entrer, avec un
 * rappel, jusqu'à un nombre de connexions fixé dans les réglages.
 *
 * Le compteur ne remplace pas une mesure d'audience : il n'est lu que pour
 * cette décision, et ne sert à profiler personne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('login_count')->default(0)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('login_count');
        });
    }
};
