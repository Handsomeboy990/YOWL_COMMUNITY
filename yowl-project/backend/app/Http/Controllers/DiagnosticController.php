<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DiagnosticController extends Controller
{
    /**
     * What the application is actually talking to.
     *
     * A deployment can look healthy while writing to the wrong place. Ours ran
     * its migrations from scratch on every restart, because DB_CONNECTION was
     * not what anybody believed: the container wrote to a local SQLite file
     * that died with it, while the managed database stayed empty. Nothing in
     * the logs said so, and a host without a shell gives no way to ask.
     *
     * This endpoint asks, in one call. It reports drivers and reachability,
     * never a credential: no password, no host, no connection string.
     */
    public function __invoke(Request $request, string $token)
    {
        $attendu = (string) config('services.cron.token');

        if ($attendu === '' || ! hash_equals($attendu, $token)) {
            return response()->json(['success' => false, 'message' => 'Introuvable.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'application' => [
                    'environnement' => config('app.env'),
                    'debug' => config('app.debug'),
                    'langue' => config('app.locale'),
                    'adresse' => config('app.url'),
                    'frontend' => config('app.frontend_url'),
                ],
                'base_de_donnees' => $this->baseDeDonnees(),
                'cache' => $this->cache(),
                'sessions' => $this->sessions(),
                'email' => [
                    'pilote' => config('mail.default'),
                    'hote' => config('mail.default') === 'smtp'
                        ? (config('mail.mailers.smtp.host') ?: 'NON RENSEIGNE')
                        : null,
                    'expediteur' => config('mail.from.address'),
                ],
                'diffusion' => config('broadcasting.default'),
                'medias' => [
                    'disque' => config('filesystems.media'),
                    'bucket' => config('filesystems.disks.s3.bucket') ?: null,
                ],
                'origines_autorisees' => config('cors.allowed_origins'),
            ],
            'message' => 'Diagnostic retrieved successfully.',
        ]);
    }

    /**
     * The single most useful line: which engine, and does it hold anything.
     */
    private function baseDeDonnees(): array
    {
        $connexion = config('database.default');
        $pilote = config("database.connections.{$connexion}.driver");

        try {
            $migrations = DB::table('migrations')->count();
            $membres = DB::table('users')->count();

            return [
                'connexion' => $connexion,
                'pilote' => $pilote,
                'base' => $pilote === 'sqlite'
                    ? config("database.connections.{$connexion}.database")
                    : config("database.connections.{$connexion}.database"),
                'joignable' => true,
                'migrations_appliquees' => $migrations,
                'membres' => $membres,
                // Le drapeau qui compte : une base éphémère repart de zéro à
                // chaque redémarrage, et personne ne s'en aperçoit.
                'persistante' => $pilote !== 'sqlite',
                'avertissement' => $pilote === 'sqlite'
                    ? 'SQLite dans un conteneur ne survit pas au redémarrage. '
                        .'Vérifie DB_CONNECTION, DB_HOST, DB_DATABASE.'
                    : null,
            ];
        } catch (\Throwable $exception) {
            return [
                'connexion' => $connexion,
                'pilote' => $pilote,
                'joignable' => false,
                'erreur' => class_basename($exception),
            ];
        }
    }

    private function cache(): array
    {
        $magasin = config('cache.default');

        try {
            Cache::put('yowl.diagnostic', 'ok', 10);
            $lu = Cache::get('yowl.diagnostic') === 'ok';
            Cache::forget('yowl.diagnostic');

            return ['magasin' => $magasin, 'fonctionne' => $lu];
        } catch (\Throwable $exception) {
            return ['magasin' => $magasin, 'fonctionne' => false, 'erreur' => class_basename($exception)];
        }
    }

    /**
     * Sessions are not used by the API, but a broken store used to take the
     * whole site down. Worth knowing before it does again.
     */
    private function sessions(): array
    {
        $pilote = config('session.driver');

        try {
            if ($pilote === 'database') {
                DB::table(config('session.table', 'sessions'))->count();
            }

            return ['pilote' => $pilote, 'utilisable' => true];
        } catch (\Throwable $exception) {
            return ['pilote' => $pilote, 'utilisable' => false, 'erreur' => class_basename($exception)];
        }
    }
}
