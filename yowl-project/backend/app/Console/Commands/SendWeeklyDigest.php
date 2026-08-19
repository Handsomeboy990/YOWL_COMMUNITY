<?php

namespace App\Console\Commands;

use App\Mail\WeeklyDigest;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Review;
use App\Models\ReviewReaction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendWeeklyDigest extends Command
{
    protected $signature = 'yowl:send-digest {--force : Ignorer le délai de sept jours}';

    protected $description = 'Send the weekly digest to the members who opted in';

    /**
     * Sends each member what happened on the subjects and people they follow.
     *
     * Members are processed in chunks and each send is guarded, so one bad
     * address does not stop the run. The seven day guard means running the
     * command twice on a Monday does not mail everybody twice.
     */
    public function handle(): int
    {
        $sent = 0;
        $skipped = 0;

        User::query()
            ->where('digest_optin', true)
            ->where('is_active', true)
            ->whereNull('anonymized_at')
            ->whereNotNull('email_verified_at')
            ->when(! $this->option('force'), fn ($q) => $q->where(
                fn ($inner) => $inner->whereNull('digest_sent_at')
                    ->orWhere('digest_sent_at', '<=', now()->subDays(7))
            ))
            ->chunkById(100, function ($users) use (&$sent, &$skipped) {
                foreach ($users as $user) {
                    try {
                        $this->sendTo($user);
                        $sent++;
                    } catch (\Throwable $e) {
                        // Une adresse morte ne doit pas arreter la tournee.
                        report($e);
                        $skipped++;
                    }
                }
            });

        $this->info("Résumé envoyé à {$sent} membre(s), {$skipped} échec(s).");

        return self::SUCCESS;
    }

    private function sendTo(User $user): void
    {
        $since = now()->subWeek();

        $followedUsers = Follow::where('user_id', $user->id)
            ->where('followable_type', User::class)->pluck('followable_id');
        $followedTags = Follow::where('user_id', $user->id)
            ->where('followable_type', Tag::class)->pluck('followable_id');

        $reviews = Review::query()
            ->where('is_published', true)
            ->where('created_at', '>=', $since)
            ->where('user_id', '!=', $user->id)
            ->when($followedUsers->isNotEmpty() || $followedTags->isNotEmpty(), function ($query) use ($followedUsers, $followedTags) {
                $query->where(function ($q) use ($followedUsers, $followedTags) {
                    $q->whereIn('user_id', $followedUsers);
                    if ($followedTags->isNotEmpty()) {
                        $q->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $followedTags));
                    }
                });
            })
            ->with('user:id,username')
            ->withCount('comments')
            ->orderByDesc('score')
            ->limit(5)
            ->get();

        $mesAvis = $user->reviews()->pluck('id');

        $activity = [
            'received' => ReviewReaction::whereIn('review_id', $mesAvis)
                ->where('created_at', '>=', $since)
                ->where('user_id', '!=', $user->id)
                ->count(),
            'comments' => Comment::whereIn('review_id', $mesAvis)
                ->where('created_at', '>=', $since)
                ->where('user_id', '!=', $user->id)
                ->count(),
        ];

        // Rien de neuf et aucune activite : on n'envoie pas un message vide.
        if ($reviews->isEmpty() && $activity['received'] === 0 && $activity['comments'] === 0) {
            $user->forceFill(['digest_sent_at' => now()])->saveQuietly();

            return;
        }

        if (! $user->digest_token) {
            $user->forceFill(['digest_token' => Str::random(48)])->saveQuietly();
        }

        $url = rtrim(config('app.url'), '/').'/api/digest/unsubscribe/'.$user->digest_token;

        Mail::to($user->email)->send(new WeeklyDigest($user, $reviews, $activity, $url));

        $user->forceFill(['digest_sent_at' => now()])->saveQuietly();
    }
}
