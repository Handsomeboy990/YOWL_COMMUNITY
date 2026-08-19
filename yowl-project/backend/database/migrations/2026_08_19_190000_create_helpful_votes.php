<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A second dimension beside the like.
     *
     * On a product about opinions, "j'aime" is ambiguous: nobody can tell
     * whether the reader approves of the thing being reviewed or of the
     * quality of the review. "Cet avis m'a aidé" separates popularity from
     * usefulness and gives ranking a far cleaner signal.
     */
    public function up(): void
    {
        Schema::create('helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->boolean('helpful')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'review_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->integer('nb_helpful')->default(0)->after('nb_views');
            $table->integer('nb_unhelpful')->default(0)->after('nb_helpful');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['nb_helpful', 'nb_unhelpful']);
        });
        Schema::dropIfExists('helpful_votes');
    }
};
