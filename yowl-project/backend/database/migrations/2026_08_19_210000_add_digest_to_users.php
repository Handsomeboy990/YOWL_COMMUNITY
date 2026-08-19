<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The weekly digest is the only channel that reaches somebody who left
     * three weeks ago: web push no longer reaches them, they uninstalled the
     * application. It is opt out, and the token makes unsubscribing a single
     * click with no login.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('digest_optin')->default(true)->after('is_active');
            $table->string('digest_token', 64)->nullable()->unique()->after('digest_optin');
            $table->timestamp('digest_sent_at')->nullable()->after('digest_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['digest_optin', 'digest_token', 'digest_sent_at']);
        });
    }
};
