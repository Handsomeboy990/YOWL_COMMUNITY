<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * YOWL is built around the link, and the link was rendered as a line of
     * text with a hostname. The metadata published by the cited page is
     * fetched once and stored here, so the feed can show what is actually
     * behind the address.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->json('link_preview')->nullable()->after('link');
            $table->timestamp('link_preview_at')->nullable()->after('link_preview');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['link_preview', 'link_preview_at']);
        });
    }
};
