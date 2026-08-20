<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    /**
     * Tasks driven by how long ago they last ran, not by a cron expression.
     *
     * schedule:run only fires what is due at the exact minute it is invoked,
     * which assumes it is invoked every minute. An external clock is not that:
     * it calls every five or ten minutes, drifts, and misses windows. A weekly
     * digest scheduled for Monday 09:00 would simply never be sent.
     *
     * Each entry therefore declares how stale it is allowed to get, and the
     * marker of its last run lives in the cache. On a host that never sleeps
     * the in-container scheduler covers the same ground: both may run, which
     * costs a little and breaks nothing, every command being idempotent.
     *
     * @var array<string, int> commande => intervalle minimal en secondes
     */
    private const TACHES = [
        // Un avis programmé attend son heure : la latence se voit.
        'yowl:publish-scheduled' => 0,
        'yowl:refresh-scores' => 3600,
        'yowl:send-digest' => 7 * 86400,
        'yowl:prune-pings' => 7 * 86400,
    ];

    /**
     * Run the due scheduled work, from outside.
     *
     * Every free host puts a container to sleep after a few minutes without
     * traffic, and a sleeping container runs no scheduler and no queue worker.
     * This endpoint lets an external clock do what the container cannot do for
     * itself: it is called, it wakes, it works.
     */
    public function __invoke(Request $request, string $token)
    {
        $attendu = (string) config('services.cron.token');

        if ($attendu === '' || ! hash_equals($attendu, $token)) {
            // Volontairement muet sur la raison : un appelant qui devine des
            // jetons ne doit pas apprendre s'il en existe un.
            return response()->json(['success' => false, 'message' => 'Introuvable.'], 404);
        }

        $debut = microtime(true);
        $lancees = [];

        foreach (self::TACHES as $commande => $intervalle) {
            if (! $this->estDue($commande, $intervalle)) {
                continue;
            }

            try {
                Artisan::call($commande);
                $lancees[] = $commande;
                Cache::put($this->marqueur($commande), now()->timestamp, now()->addDays(30));
            } catch (\Throwable $exception) {
                // Une tâche qui échoue ne doit pas empêcher les suivantes.
                Log::error('Réveil planifié : '.$commande.' a échoué', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        // La file est vidée dans la foulée : sur un hébergement qui s'endort,
        // le worker de supervisord ne tourne pas non plus. stop-when-empty
        // rend la main dès qu'il n'y a plus rien à traiter.
        try {
            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--max-time' => 40,
                '--tries' => 2,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Réveil planifié : file en échec', ['message' => $exception->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'duree_ms' => (int) ((microtime(true) - $debut) * 1000),
                'lancees' => $lancees,
            ],
            'message' => count($lancees).' tâche(s) exécutée(s).',
        ]);
    }

    private function estDue(string $commande, int $intervalle): bool
    {
        if ($intervalle === 0) {
            return true;
        }

        $dernier = Cache::get($this->marqueur($commande));

        return ! $dernier || (now()->timestamp - (int) $dernier) >= $intervalle;
    }

    private function marqueur(string $commande): string
    {
        return 'yowl.cron.'.str_replace(':', '.', $commande);
    }
}
