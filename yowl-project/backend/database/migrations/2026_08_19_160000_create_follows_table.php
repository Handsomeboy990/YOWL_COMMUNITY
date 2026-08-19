<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The relation the platform was missing entirely.
     *
     * Without it every member saw the same chronological feed and nothing
     * distinguished a second visit from a first. One polymorphic table covers
     * both cases that matter: following a person, and following a subject.
     * Following a tag matters as much as following a person and costs less
     * socially, since somebody who knows nobody can still compose a feed.
     */
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // morphs() pose deja l'index sur le couple type et identifiant :
            // le redeclarer ici entrait en collision de nom.
            $table->morphs('followable');
            $table->timestamps();

            $table->unique(['user_id', 'followable_type', 'followable_id'], 'follows_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
