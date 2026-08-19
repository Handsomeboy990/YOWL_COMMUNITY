<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two relations a social platform cannot do without.
     *
     * Blocking, because the only answer to somebody bothering you was to
     * report them and wait for a moderator, which is not acceptable on a
     * platform open from thirteen. Bookmarks, because keeping an opinion to
     * read later is the first reflex on a feed of links, and it was the most
     * asked for thing in the suggestions box.
     */
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'blocked_id']);
            $table->index('blocked_id');
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'review_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('blocks');
    }
};
