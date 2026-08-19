<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Support\FeedScore;
use Illuminate\Console\Command;

class PublishScheduledReviews extends Command
{
    protected $signature = 'yowl:publish-scheduled';

    protected $description = 'Publish the reviews whose scheduled hour has passed';

    /**
     * Runs every five minutes, so an author picking an hour sees it honoured
     * to the nearest five minutes rather than the nearest hour.
     */
    public function handle(): int
    {
        $dus = Review::query()
            ->where('is_published', false)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->get();

        foreach ($dus as $review) {
            // La date est effacee en meme temps : la garder ferait retomber
            // l'avis dans la selection a chaque passage si la publication
            // echouait plus loin, et l'auteur verrait "programme" sur un avis
            // deja en ligne.
            $review->forceFill(['is_published' => true, 'scheduled_for' => null])->save();
            FeedScore::refresh($review);
            $this->line('Publie: avis #'.$review->id);
        }

        $this->info($dus->count().' avis publie(s).');

        return self::SUCCESS;
    }
}
