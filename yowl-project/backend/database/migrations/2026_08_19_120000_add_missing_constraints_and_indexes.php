<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Close the gaps between what the application promises and what the
     * database actually enforces, and index the columns the feed sorts on.
     */
    public function up(): void
    {
        $this->deduplicateUsernames();
        $this->deduplicateReactions('review_reactions', 'review_id');
        $this->deduplicateReactions('comment_reactions', 'comment_id');

        Schema::table('users', function (Blueprint $table) {
            // Le formulaire valide deja l'unicite du pseudo, mais rien ne
            // l'imposait : deux inscriptions simultanees passaient.
            $table->unique('username');
            $table->timestamp('anonymized_at')->nullable()->after('is_active');
            $table->index('birthdate');

            // Un compte anonymise n'a plus de date de naissance a conserver.
            $table->date('birthdate')->nullable()->change();
        });

        Schema::table('review_reactions', function (Blueprint $table) {
            // Une personne n'a qu'un avis par review. Sans contrainte, deux
            // clics simultanes creaient deux lignes et faussaient le compteur.
            $table->unique(['user_id', 'review_id'], 'review_reactions_unique_per_user');
        });

        Schema::table('comment_reactions', function (Blueprint $table) {
            $table->unique(['user_id', 'comment_id'], 'comment_reactions_unique_per_user');
        });

        Schema::table('reviews', function (Blueprint $table) {
            // Le fil trie sur ces colonnes a chaque appel.
            $table->index(['is_published', 'created_at'], 'reviews_feed_index');
            $table->index('nb_like');
            $table->index('user_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['review_id', 'created_at'], 'comments_review_index');
            $table->index('parent_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_review_index');
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_feed_index');
            $table->dropIndex(['nb_like']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('comment_reactions', function (Blueprint $table) {
            $table->dropUnique('comment_reactions_unique_per_user');
        });

        Schema::table('review_reactions', function (Blueprint $table) {
            $table->dropUnique('review_reactions_unique_per_user');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('anonymized_at');
            $table->dropIndex(['birthdate']);
        });
    }

    /**
     * Rename duplicate usernames before the unique index is created,
     * so the migration does not fail on a database that already has some.
     */
    private function deduplicateUsernames(): void
    {
        $duplicates = DB::table('users')
            ->select('username')
            ->groupBy('username')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('username');

        foreach ($duplicates as $username) {
            $ids = DB::table('users')->where('username', $username)->orderBy('id')->pluck('id');
            foreach ($ids->slice(1) as $id) {
                DB::table('users')->where('id', $id)->update(['username' => $username.'-'.$id]);
            }
        }
    }

    /**
     * Keep the most recent reaction of each person on each piece of content.
     */
    private function deduplicateReactions(string $table, string $target): void
    {
        $duplicates = DB::table($table)
            ->select('user_id', $target)
            ->groupBy('user_id', $target)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            $keep = DB::table($table)
                ->where('user_id', $row->user_id)
                ->where($target, $row->{$target})
                ->orderByDesc('id')
                ->value('id');

            DB::table($table)
                ->where('user_id', $row->user_id)
                ->where($target, $row->{$target})
                ->where('id', '!=', $keep)
                ->delete();
        }
    }
};
