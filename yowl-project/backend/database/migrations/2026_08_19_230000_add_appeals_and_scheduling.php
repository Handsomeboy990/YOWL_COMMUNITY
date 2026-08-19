<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two things a moderated member and an editorial team both need.
     *
     * An appeal, because being told your content was hidden without any way to
     * answer is how people leave. And a publication date, because writing a
     * review and choosing when it goes out are two different moments.
     */
    public function up(): void
    {
        Schema::create('appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('appealable');
            $table->text('message');
            $table->string('status')->default('pending');
            $table->text('response')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            // Un recours par personne et par contenu : insister n'aide pas.
            $table->unique(['user_id', 'appealable_type', 'appealable_id'], 'appeals_unique');
            $table->index(['status', 'created_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('scheduled_for')->nullable()->after('is_published');
            $table->index(['scheduled_for', 'is_published'], 'reviews_scheduled_index');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_scheduled_index');
            $table->dropColumn('scheduled_for');
        });
        Schema::dropIfExists('appeals');
    }
};
