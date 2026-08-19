<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A score computed on write and indexed, so the feed can order by
     * something other than the clock without sorting in PHP.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->double('score')->default(0)->after('nb_views');
            $table->index(['is_published', 'score'], 'reviews_score_index');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_score_index');
            $table->dropColumn('score');
        });
    }
};
