<?php

namespace App\Observers;

use App\Support\Cached;

/**
 * Invalidates the counters when the content they count changes.
 *
 * Attached to reviews, comments and users. The cached figures were previously
 * left to expire on their own, so publishing a review and immediately looking
 * at the feed showed the old total, which reads as a bug to anybody who just
 * posted.
 */
class ContentObserver
{
    public function created(): void
    {
        Cached::forgetContentCounters();
    }

    public function deleted(): void
    {
        Cached::forgetContentCounters();
    }

    public function updated(): void
    {
        Cached::forgetContentCounters();
    }
}
