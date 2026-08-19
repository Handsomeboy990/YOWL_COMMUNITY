<?php

use App\Support\LinkNormaliser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The normalised form of the cited address, indexed.
     *
     * Comparing raw links would miss the same article shared with a tracking
     * parameter, which is most of the time.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('link_fingerprint', 512)->nullable()->after('link');
            $table->index('link_fingerprint');
        });

        // Les avis deja publies doivent participer a la detection, sinon le
        // premier doublon detecte serait le troisieme partage de l'adresse.
        DB::table('reviews')->whereNotNull('link')->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('reviews')->where('id', $row->id)->update([
                        'link_fingerprint' => LinkNormaliser::fingerprint($row->link),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['link_fingerprint']);
            $table->dropColumn('link_fingerprint');
        });
    }
};
