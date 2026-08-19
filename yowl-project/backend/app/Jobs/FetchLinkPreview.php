<?php

namespace App\Jobs;

use App\Models\Review;
use App\Services\LinkPreviewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Fetches the metadata of a cited link out of the request.
 *
 * Publishing must not wait on a third party server: a slow page would make
 * the publish button spin for six seconds, and an unreachable one would make
 * it fail for a reason that has nothing to do with the member's action.
 */
class FetchLinkPreview implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 20;

    public function __construct(public int $reviewId) {}

    public function handle(LinkPreviewService $previews): void
    {
        $review = Review::find($this->reviewId);
        if (! $review || ! $review->link) {
            return;
        }

        $preview = $previews->fetch($review->link);

        // On horodate meme un echec, pour ne pas retenter a chaque affichage.
        $review->forceFill([
            'link_preview' => $preview,
            'link_preview_at' => now(),
        ])->saveQuietly();
    }
}
