<?php

namespace App\Observers;

use App\Support\Cached;

/**
 * A tag nobody had used before must appear in the filter panel at once,
 * not when the list happens to expire.
 */
class TagObserver
{
    public function created(): void
    {
        Cached::forget(Cached::TAGS);
        Cached::forgetContentCounters();
    }

    public function deleted(): void
    {
        Cached::forget(Cached::TAGS);
    }
}
