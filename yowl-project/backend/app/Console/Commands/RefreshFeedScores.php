<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Support\FeedScore;
use Illuminate\Console\Command;

class RefreshFeedScores extends Command
{
    protected $signature = 'yowl:refresh-scores';

    protected $description = 'Recompute the feed score of every review';

    /**
     * The score decays with age, so it has to be recomputed periodically even
     * when nothing about the review itself changes. Scheduled hourly.
     */
    public function handle(): int
    {
        $count = 0;

        Review::with('comments:id,review_id')->chunkById(200, function ($reviews) use (&$count) {
            foreach ($reviews as $review) {
                FeedScore::refresh($review);
                $count++;
            }
        });

        $this->info($count.' avis reclassés.');

        return self::SUCCESS;
    }
}
