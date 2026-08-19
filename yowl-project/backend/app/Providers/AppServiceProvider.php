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
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
