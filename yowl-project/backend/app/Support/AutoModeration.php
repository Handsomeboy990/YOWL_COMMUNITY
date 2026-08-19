<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\Report;
use App\Models\Review;
use App\Notifications\ContentAutoHidden;

/**
 * Takes obviously contested content out of the feed while a human decides.
 *
 * The queue was entirely manual: something reported by ten people stayed
 * visible until a moderator happened to open the console. The threshold is a
 * setting, so it can be raised, lowered, or switched off from the
 * administration without a deployment.
 *
 * Hiding is never deletion, and it is always reversible: publishing the
 * review again from the console puts it straight back.
 */
class AutoModeration
{
    /**
     * Called after a report is filed. Returns true when the content was hidden.
     */
    public static function evaluate(Report $report): bool
    {
        $threshold = Settings::get('moderation.auto_hide_threshold');
        if (! $threshold) {
            return false;
        }

        $reportable = $report->reportable;
        if (! $reportable instanceof Review) {
            // Un commentaire n'a pas d'etat publie : il reste a la moderation
            // humaine, la suppression etant le seul geste possible dessus.
            return false;
        }

        if (! $reportable->is_published) {
            return false;
        }

        // Des signalements distincts, pas la meme personne qui insiste : la
        // contrainte d'unicite en base garantit deja un signalement par
        // personne et par contenu.
        $pending = Report::where('reportable_type', Review::class)
            ->where('reportable_id', $reportable->id)
            ->where('status', Report::STATUS_PENDING)
            ->count();

        if ($pending < $threshold) {
            return false;
        }

        $reportable->forceFill(['is_published' => false])->save();

        if ($reportable->user) {
            $reportable->user->notify(new ContentAutoHidden($report->user, $reportable->id, $pending));
        }

        return true;
    }

    /**
     * How many pending reports a piece of content carries, for the queue to
     * show the moderator what is most contested.
     */
    public static function pendingCountFor(Review|Comment $content): int
    {
        return Report::where('reportable_type', $content::class)
            ->where('reportable_id', $content->getKey())
            ->where('status', Report::STATUS_PENDING)
            ->count();
    }
}
