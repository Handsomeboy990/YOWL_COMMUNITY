<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legal pages held in the database rather than in the components.
     *
     * The charter, the privacy notice and the terms were written into Vue
     * files, so correcting a sentence meant a deployment, and nobody outside
     * the development team could touch a text that is legally binding.
     *
     * Two bodies per page: what the public reads, and what is being written.
     * Publishing copies the draft over the published body, so an unfinished
     * edit is never visible.
     */
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->longText('draft_body')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
