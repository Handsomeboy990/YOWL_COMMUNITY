<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per minute of presence, which is what session time needs.
     *
     * Publication timestamps say when somebody wrote, never how long they
     * stayed, so average session time cannot be derived from the content
     * tables. A ping is the smallest thing that answers it: the browser says
     * "still here" while the tab is visible, and sessions are cut where the
     * silence exceeds the threshold.
     */
    public function up(): void
    {
        Schema::create('activity_pings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('pinged_at');

            // La lecture se fait toujours par membre puis par date.
            $table->index(['user_id', 'pinged_at']);
            $table->index('pinged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_pings');
    }
};
