<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use App\Observers\ContentObserver;
use App\Observers\TagObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->guardBroadcastConnection();
    }

    /**
     * Refuse to let a mistyped broadcast driver take the whole site down.
     *
     * routes/channels.php resolves the driver while the application boots. An
     * unknown name throws there, before anything is served: no pages, no
     * queue, not even a migration. That is a wildly disproportionate outcome
     * for a feature this deployment does not use, and the name is easy to get
     * wrong since MAIL_MAILER and BROADCAST_CONNECTION sit next to each other
     * and both accept the value "log".
     *
     * An unknown name now falls back to the null driver and says so in the
     * logs, which degrades broadcasting instead of stopping everything.
     */
    private function guardBroadcastConnection(): void
    {
        $demandee = config('broadcasting.default');
        $connues = array_keys(config('broadcasting.connections', []));

        if ($demandee === null || in_array($demandee, $connues, true)) {
            return;
        }

        Log::error('BROADCAST_CONNECTION vaut une valeur inconnue, diffusion désactivée.', [
            'recu' => $demandee,
            'attendu' => $connues,
        ]);

        config(['broadcasting.default' => 'null']);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        $this->configureRateLimiting();
        $this->registerCacheInvalidation();
    }

    /**
     * Wire the observers that keep the cached counters honest.
     *
     * Doing it here rather than in each controller means a future write path
     * inherits the invalidation instead of having to remember it.
     */
    private function registerCacheInvalidation(): void
    {
        Review::observe(ContentObserver::class);
        Comment::observe(ContentObserver::class);
        User::observe(ContentObserver::class);
        Tag::observe(TagObserver::class);
    }

    /**
     * The baseline limit applied to every API route.
     *
     * A signed in member is counted on their identifier, so several members
     * behind one address do not consume each other's allowance. Anonymous
     * traffic is counted on the address and gets a tighter budget, since the
     * public endpoints are the ones worth hammering.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by('user:'.$request->user()->id)
                : Limit::perMinute(60)->by('ip:'.$request->ip());
        });
    }
}
