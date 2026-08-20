<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Every cached value of the application, declared in one place.
 *
 * Caching used to be three unrelated calls to Cache::remember scattered across
 * controllers, with the keys written as literals and no invalidation: a new
 * review left the community counters wrong for five minutes, and a new tag
 * stayed invisible for ten. Naming the entries here means the code that writes
 * data can invalidate what that write makes stale, without knowing how the
 * cache is keyed.
 */
class Cached
{
    /** Community counters shown on the feed and the landing page. */
    public const KPI = 'kpi.community';

    /** The public tag list backing the filter panel. */
    public const TAGS = 'tags.all';

    /** Administration dashboard counters. */
    public const ADMIN_STATS = 'admin.stats';

    /** The five growth indicators, whose cohort pass walks every member. */
    public const GROWTH = 'admin.growth';

    /**
     * Time to live, in seconds, per entry.
     *
     * The counters tolerate being a few minutes late; the tag list changes
     * only when somebody publishes a tag nobody used before.
     */
    private const TTL = [
        self::KPI => 300,
        self::TAGS => 600,
        self::ADMIN_STATS => 60,
        // Quinze minutes : le calcul parcourt chaque membre de chaque cohorte,
        // et personne ne lit une courbe de croissance assez souvent pour
        // justifier de la refaire a chaque ouverture.
        self::GROWTH => 900,
    ];

    /**
     * Read an entry, computing it on a miss.
     */
    public static function remember(string $key, callable $compute): mixed
    {
        return Cache::remember($key, self::TTL[$key] ?? 60, $compute);
    }

    /**
     * Drop one entry.
     */
    public static function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Drop everything that a change in published content makes stale.
     *
     * Called from the observers rather than from each controller, so a new
     * write path cannot forget to do it.
     */
    public static function forgetContentCounters(): void
    {
        Cache::forget(self::KPI);
        Cache::forget(self::ADMIN_STATS);
    }

    /**
     * Drop every entry this class owns. Used after a seed or a restore.
     */
    public static function flush(): void
    {
        foreach (array_keys(self::TTL) as $key) {
            Cache::forget($key);
        }
        Settings::forget();
    }
}
