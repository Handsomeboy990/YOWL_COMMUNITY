<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deux identifiants qui n'existent que si la personne les a acceptés.
 *
 * La mesure de base ne dépose rien sur l'appareil et compte des visites. Elle
 * ne peut donc pas distinguer dix visites d'une personne de dix visites de dix
 * personnes, ni dire ce qu'est une session. Ces deux colonnes lèvent cette
 * limite, au prix d'un identifiant stocké sur l'appareil, qui exige un accord
 * explicite et révocable.
 *
 * Les deux restent nuls quand l'accord n'est pas donné, et la mesure continue
 * exactement comme avant : refuser ne rend pas invisible, cela retire
 * seulement le recoupement entre deux pages.
 *
 * Les deux valeurs sont tirées au hasard par le navigateur. Elles ne dérivent
 * d'aucune caractéristique de l'appareil, ne se recoupent avec aucun compte,
 * et ne valent que pour ce site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_visits', function (Blueprint $table) {
            // Persistant, treize mois au plus, la durée maximale que la CNIL
            // admet pour un consentement de mesure d'audience.
            $table->string('visitor_id', 36)->nullable()->after('is_member');

            // Glissant, une session s'arrête après trente minutes sans page.
            $table->string('session_id', 36)->nullable()->after('visitor_id');

            $table->index(['visitor_id', 'visited_at']);
            $table->index(['session_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::table('page_visits', function (Blueprint $table) {
            $table->dropIndex(['visitor_id', 'visited_at']);
            $table->dropIndex(['session_id', 'visited_at']);
            $table->dropColumn(['visitor_id', 'session_id']);
        });
    }
};
