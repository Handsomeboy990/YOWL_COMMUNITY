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
                'email' => $this->email(),
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
    /**
     * L'état de l'envoi d'email, jusqu'à la joignabilité du relais.
     *
     * Le test de connexion est ce qui distingue deux pannes qui se
     * ressemblent : des identifiants refusés, et un port sortant filtré par
     * l'hébergeur. Dans le second cas, aucun fournisseur SMTP ne marchera
     * jamais, quel qu'il soit, et il faut passer par une API en HTTPS. Sans
     * cette mesure, on change de fournisseur en boucle sans rien résoudre.
     *
     * Aucun identifiant n'est renvoyé, seulement ce qui est nécessaire pour
     * décider quoi faire.
     */
    private function email(): array
    {
        $pilote = config('mail.default');

        $etat = [
            'pilote' => $pilote,
            'expediteur' => config('mail.from.address') ?: 'NON RENSEIGNE',
        ];

        if ($pilote === 'mailjet') {
            // Rien à sonder : l'appel passe par le port 443, ouvert partout.
            // Seules les clés peuvent manquer.
            $etat['cle_api'] = config('mail.mailers.mailjet.key') ? 'renseignee' : 'ABSENTE';
            $etat['cle_secrete'] = config('mail.mailers.mailjet.secret') ? 'renseignee' : 'ABSENTE';
            $etat['lecture'] = 'Envoi par API HTTPS : aucun port SMTP en jeu. '
                ."Si l'envoi echoue, la cause est presque toujours un expediteur non valide chez Mailjet.";

            return $etat;
        }

        if ($pilote !== 'smtp') {
            return $etat;
        }

        $hote = (string) config('mail.mailers.smtp.host');
        $port = (int) config('mail.mailers.smtp.port');

        $etat['hote'] = $hote ?: 'NON RENSEIGNE';
        $etat['port'] = $port ?: null;
        $etat['identifiant'] = config('mail.mailers.smtp.username') ? 'renseigne' : 'ABSENT';
        $etat['mot_de_passe'] = config('mail.mailers.smtp.password') ? 'renseigne' : 'ABSENT';
        $etat['delai'] = config('mail.mailers.smtp.timeout');

        if (! $hote || ! $port) {
            return $etat;
        }

        // Cinq secondes : on cherche à savoir si la porte s'ouvre, pas à
        // dialoguer. Un port filtré ne répond ni oui ni non, il absorbe, et
        // c'est le délai qui tranche.
        $debut = microtime(true);
        $flux = @fsockopen($hote, $port, $numero, $texte, 5);
        $duree = round((microtime(true) - $debut) * 1000);

        if ($flux) {
            fclose($flux);
            $etat['relais_joignable'] = true;
            $etat['relais_delai_ms'] = $duree;

            return $etat;
        }

        $etat['relais_joignable'] = false;
        $etat['relais_delai_ms'] = $duree;
        $etat['relais_erreur'] = $texte ?: 'aucune reponse';
        $etat['lecture'] = $duree >= 4500
            ? "Le port ne repond pas du tout : il est probablement filtre par l'hebergeur. "
                .'Aucun fournisseur SMTP ne fonctionnera, il faut une API en HTTPS.'
            : "La connexion est refusee : hote ou port errone, ou service indisponible.";

        return $etat;
    }

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
