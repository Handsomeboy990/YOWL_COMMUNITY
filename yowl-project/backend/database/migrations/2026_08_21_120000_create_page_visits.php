<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mesure d'audience, tenue à distance de toute donnée personnelle.
 *
 * Ce que la table ne contient pas est aussi décidé que ce qu'elle contient :
 * ni adresse IP, ni identifiant de membre, ni identifiant de session, ni
 * empreinte de navigateur, ni URL de provenance complète. Aucune ligne ne se
 * rattache à une personne, et deux visites de la même personne sont
 * indistinguables de deux visites de deux personnes.
 *
 * C'est ce qui permet de mesurer l'audience sans demander de consentement :
 * la CNIL exempte les mesures d'audience strictement anonymes, à finalité
 * limitée et sans recoupement. Une colonne user_id ferait basculer la table
 * dans un autre régime et imposerait une bannière.
 *
 * Le chemin est un motif de route, jamais une URL réelle : « /reviews/:id »
 * et non « /reviews/1234 ». La cardinalité reste basse, et surtout on
 * n'apprend pas quel avis une personne est allée lire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();

            // Motif de route. Court : les segments variables sont remplacés.
            $table->string('path', 120);

            // Hôte de provenance seul, jamais le chemin ni les paramètres,
            // qui portent souvent le terme recherché ou un identifiant.
            $table->string('referrer_host', 120)->nullable();

            // mobile, tablet ou desktop. Trois valeurs, pas une empreinte.
            $table->string('device', 10);

            // Membre ou visiteur, sans dire lequel. La distinction sert à
            // lire l'entonnoir d'inscription, elle ne désigne personne.
            $table->boolean('is_member')->default(false);

            $table->timestamp('visited_at');

            // Les trois lectures du tableau de bord : la courbe dans le temps,
            // le classement des pages, celui des provenances.
            $table->index('visited_at');
            $table->index(['path', 'visited_at']);
            $table->index(['referrer_host', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
