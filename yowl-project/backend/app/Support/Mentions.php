<?php

namespace App\Support;

use App\Models\Block;
use App\Models\User;

/**
 * Finds the @handles in a text and returns the members they point at.
 *
 * Notification is the whole point of a mention, and the notification system
 * already accepts a new type without rewiring, so this is a resolver and
 * nothing more.
 *
 * A mention is also a way to reach somebody who does not want to be reached,
 * which is why a member who blocked the author is never notified.
 */
class Mentions
{
    /** No more than this many people are notified by one message. */
    private const MAX = 10;

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function resolve(string $text, User $author)
    {
        preg_match_all('/(?<![\w@])@([a-zA-Z0-9._-]{3,255})/u', $text, $matches);

        $handles = collect($matches[1])
            ->map(fn ($handle) => rtrim($handle, '.'))
            ->unique()
            ->take(self::MAX);

        if ($handles->isEmpty()) {
            return collect();
        }

        $blockedBy = Block::where('blocked_id', $author->id)->pluck('user_id');

        return User::whereIn('username', $handles)
            ->where('id', '!=', $author->id)
            ->whereNotIn('id', $blockedBy)
            ->whereNull('anonymized_at')
            ->where('is_active', true)
            ->get();
    }
}
