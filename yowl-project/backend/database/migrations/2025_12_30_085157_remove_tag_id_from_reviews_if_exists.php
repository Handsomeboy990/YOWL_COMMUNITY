<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('reviews') && Schema::hasColumn('reviews', 'tag_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropForeign(['tag_id']);
                $table->dropColumn('tag_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne pas recréer tag_id car on utilise la table pivot review_tag
    }
};
