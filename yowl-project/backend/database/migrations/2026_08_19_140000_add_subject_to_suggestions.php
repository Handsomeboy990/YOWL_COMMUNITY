<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suggestions arrived as an undifferentiated pile of free text, which made
     * the moderation queue unreadable and gave the sender no way to say what
     * their message was about.
     */
    public function up(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('email');
            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropIndex(['subject']);
            $table->dropColumn('subject');
        });
    }
};
