<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Email campaigns, and who received which one.
     *
     * The recipient table is not an audit luxury: sending to a few thousand
     * addresses fails partway often enough that a resume needs to know where
     * it stopped, and a member who asks whether they were written to deserves
     * an answer that is not a guess.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('body');
            // promotion, feedback, opinion, announcement
            $table->string('purpose', 30)->default('announcement');
            // all, selected, segment
            $table->string('audience', 20)->default('all');
            $table->string('segment', 40)->nullable();
            $table->json('user_ids')->nullable();
            // draft, sending, sent, failed
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->string('error', 255)->nullable();

            // Une campagne n'atteint chaque membre qu'une fois, meme si l'envoi
            // est relance apres un echec partiel.
            $table->unique(['campaign_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Refus des campagnes, distinct du resume hebdomadaire : couper
            // l'un ne doit pas couper l'autre. Les messages de service ne
            // passent pas par les campagnes et ne sont pas concernes.
            $table->boolean('email_optout')->default(false)->after('digest_optin');
            $table->string('email_token', 64)->nullable()->after('email_optout');
            $table->index('email_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email_token']);
            $table->dropColumn(['email_optout', 'email_token']);
        });
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
    }
};
