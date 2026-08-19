<?php

namespace App\Support;

use App\Models\Review;

/**
 * Ranks a review by freshness and by the engagement it drew.
 *
 * Deliberately a readable formula rather than anything learned: it can be
 * explained to a member who asks why their review fell, and corrected when it
 * ranks the wrong thing. It is computed on write and stored in an indexed
 * column, so ordering the feed stays one indexed sort.
 *
 * The shape is the classic logarithmic decay: engagement counts on a log
 * scale, because the difference between one and ten reactions matters far
 * more than between one hundred and one hundred and ten, and age subtracts
 * steadily so that nothing stays on top for ever.
 */
class FeedScore
{
    /** Hours after which a review loses one point of score. */
    private const GRAVITY = 12;

    /** A comment is worth more than a reaction: it costs more to write. */
    private const COMMENT_WEIGHT = 2.5;

    private const LIKE_WEIGHT = 1.0;

    private const VIEW_WEIGHT = 0.05;

    public static function for(Review $review): float
    {
        $engagement = self::LIKE_WEIGHT * max(0, $review->nb_like)
            + self::COMMENT_WEIGHT * $review->comments()->count()
            + self::VIEW_WEIGHT * max(0, $review->nb_views);

        // Les avis rejetes pesent negativement, sans jamais annuler le reste.
        $engagement = max(0, $engagement - 0.5 * max(0, $review->nb_dislike));

        $ageHours = max(0, $review->created_at?->diffInHours(now()) ?? 0);

        return round(log10($engagement + 1) - ($ageHours / self::GRAVITY), 4);
    }

    /**
     * Recompute and persist, without touching updated_at or firing observers
     * that would invalidate the cache on every reaction.
     */
    public static function refresh(Review $review): void
    {
        $review->forceFill(['score' => self::for($review)])->saveQuietly();
    }
}
