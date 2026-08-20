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
        $this->guardEphemeralDatabase();
    }

    /**
     * Refuse to run production on a database that dies with the container.
     *
     * DB_CONNECTION defaults to sqlite. A deployment that forgets it, or that
     * pastes the PGHOST style variables a managed provider hands out, writes
     * to a local file instead of the managed database. Migrations run from
     * scratch on every restart, every account vanishes, and the provider's
     * console stays empty. Nothing says so: the application looks healthy
     * right up to the next restart.
     *
     * Failing the boot turns silent data loss into an obvious, self-explaining
     * crash. That is the better of the two.
     */
    private function guardEphemeralDatabase(): void
    {
        if (! $this->app->environment('production')) {
            return;
        }

        $connexion = config('database.default');
        if (config("database.connections.{$connexion}.driver") !== 'sqlite') {
            return;
        }

        $message = 'DB_CONNECTION vaut sqlite en production. '
            .'Un fichier SQLite vit dans le conteneur et disparaît avec lui : '
            .'les migrations repartent de zéro à chaque redémarrage et la base '
            .'managée reste vide. Renseigne DB_CONNECTION=pgsql, DB_HOST, '
            .'DB_PORT, DB_DATABASE, DB_USERNAME et DB_PASSWORD. Les variables '
            .'PGHOST, PGDATABASE, PGUSER et PGPASSWORD sont acceptées en repli.';

        Log::critical($message);

        throw new \RuntimeException($message);
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
