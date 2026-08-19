<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Full text search over reviews, members and tags.
 *
 * The previous search ran a LIKE on the content column. It ignored accents,
 * so "cinema" never found "cinéma"; it ranked nothing, so the oldest match
 * came out first as often as the best one; and it looked at the review body
 * only, missing the author and the subject.
 *
 * PostgreSQL does all of this natively with a French dictionary, which is what
 * production runs on. SQLite has none of it, so development falls back to a
 * normalised LIKE that at least handles accents and word order.
 */
class ReviewSearch
{
    /**
     * Words shorter than this are ignored, they match everything.
     */
    private const MIN_TERM = 2;

    public static function apply(Builder $query, string $raw): Builder
    {
        $terms = self::terms($raw);
        if (! $terms) {
            return $query;
        }

        return self::driver() === 'pgsql'
            ? self::postgres($query, $terms)
            : self::portable($query, $terms);
    }

    /**
     * Order by how well the row matches, best first.
     */
    public static function order(Builder $query, string $raw): Builder
    {
        $terms = self::terms($raw);
        if (! $terms) {
            return $query;
        }

        if (self::driver() === 'pgsql') {
            $vector = self::vectorExpression();
            $needle = implode(' & ', array_map(fn ($t) => $t.':*', $terms));

            return $query->orderByRaw(
                "ts_rank({$vector}, to_tsquery('french', ?)) DESC",
                [$needle]
            );
        }

        // Sans classement natif, le plus recent d'abord reste le moins mauvais
        // ordre, et il est previsible.
        return $query->orderByDesc('created_at');
    }

    /**
     * @param  array<int, string>  $terms
     */
    private static function postgres(Builder $query, array $terms): Builder
    {
        $needle = implode(' & ', array_map(fn ($t) => $t.':*', $terms));

        return $query->whereRaw(
            self::vectorExpression().' @@ to_tsquery(\'french\', ?)',
            [$needle]
        );
    }

    /**
     * The searchable document: the review, its author and its tags.
     */
    private static function vectorExpression(): string
    {
        return "to_tsvector('french',
            coalesce(reviews.content, '') || ' ' ||
            coalesce(reviews.link, '') || ' ' ||
            coalesce((SELECT username FROM users WHERE users.id = reviews.user_id), '') || ' ' ||
            coalesce((SELECT string_agg(tags.name, ' ')
                      FROM tags
                      JOIN review_tag ON review_tag.tag_id = tags.id
                      WHERE review_tag.review_id = reviews.id), '')
        )";
    }

    /**
     * Every term must appear somewhere, in any order, accents ignored.
     *
     * @param  array<int, string>  $terms
     */
    private static function portable(Builder $query, array $terms): Builder
    {
        return $query->where(function ($outer) use ($terms) {
            foreach ($terms as $term) {
                $needle = '%'.$term.'%';
                $outer->where(function ($q) use ($needle) {
                    $q->whereRaw('LOWER('.self::unaccent('reviews.content').') LIKE ?', [$needle])
                        ->orWhereRaw('LOWER('.self::unaccent('reviews.link').') LIKE ?', [$needle])
                        ->orWhereHas('user', fn ($u) => $u->whereRaw(
                            'LOWER('.self::unaccent('username').') LIKE ?', [$needle]
                        ))
                        ->orWhereHas('tags', fn ($t) => $t->whereRaw(
                            'LOWER('.self::unaccent('name').') LIKE ?', [$needle]
                        ));
                });
            }
        });
    }

    /**
     * Strip the accents SQLite cannot fold on its own.
     *
     * Only the letters French actually uses, which keeps the expression short
     * enough to stay readable in a query plan.
     */
    private static function unaccent(string $column): string
    {
        $pairs = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];

        $expression = $column;
        foreach ($pairs as $from => $to) {
            $expression = "REPLACE({$expression}, '{$from}', '{$to}')";
        }

        return $expression;
    }

    /**
     * Normalise the query the same way the columns are normalised.
     *
     * @return array<int, string>
     */
    private static function terms(string $raw): array
    {
        $normalised = mb_strtolower(trim($raw));
        $normalised = strtr($normalised, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);

        return collect(preg_split('/[^a-z0-9]+/u', $normalised, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($t) => mb_strlen($t) >= self::MIN_TERM)
            ->take(6)
            ->values()
            ->all();
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }
}
