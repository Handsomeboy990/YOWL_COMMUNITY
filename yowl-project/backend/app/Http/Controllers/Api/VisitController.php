<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use App\Support\Audience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Enregistre une consultation de page.
 *
 * Appelée par le navigateur à chaque changement de route. Le client ne fournit
 * que deux choses, et les deux sont retraitées avant d'être écrites : le
 * chemin, ramené à un motif de route connu, et la provenance, ramenée à son
 * seul hôte. Tout le reste est déduit ici.
 */
class VisitController extends Controller
{
    /**
     * Familles d'appareil reconnues dans l'agent utilisateur.
     *
     * La tablette se teste avant le mobile : les agents des tablettes
     * Android contiennent « Mobile » aussi, et l'ordre inverse les rangerait
     * toutes avec les téléphones.
     */
    private const TABLETTE = '/ipad|tablet|playbook|silk|(android(?!.*mobile))/i';

    private const MOBILE = '/android|iphone|ipod|iemobile|blackberry|opera mini|mobile/i';

    /**
     * Agents qui ne sont pas des gens.
     *
     * La plupart des robots n'exécutent pas de JavaScript et n'arrivent donc
     * jamais ici, mais ceux de Google et de quelques autres le font. Les
     * compter gonflerait l'audience d'un trafic qui ne lit rien.
     */
    private const ROBOT = '/bot|crawl|spider|slurp|headless|lighthouse|preview|monitor|curl|wget|python-requests/i';

    public function __invoke(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            // Deux identifiants tirés au hasard par le navigateur, présents
            // seulement si la personne a accepté la mesure détaillée. Le
            // format est imposé pour qu'aucune autre valeur ne s'y glisse :
            // ces colonnes ne doivent jamais recevoir autre chose qu'un
            // identifiant opaque, surtout pas un pseudo ou une adresse.
            'visitor' => ['nullable', 'string', 'uuid'],
            'session' => ['nullable', 'string', 'uuid'],
        ]);

        $agent = (string) $request->userAgent();

        // Un robot reçoit la même réponse qu'une personne : lui répondre
        // autrement reviendrait à lui apprendre qu'il a été reconnu, sans
        // aucun bénéfice.
        if ($agent === '' || preg_match(self::ROBOT, $agent)) {
            return response()->json(['success' => true]);
        }

        PageVisit::create([
            'path' => Audience::normaliserChemin($donnees['path']),
            // La provenance vient du client, et l'en-tête Referer n'est qu'un
            // recours.
            //
            // Sur une requête XHR, le navigateur met dans Referer l'adresse de
            // la page courante, c'est à dire une page de ce site : cet en-tête
            // désigne donc toujours une provenance interne, que
            // normaliserProvenance écarte. La provenance externe réelle vit
            // dans document.referrer, côté navigateur, et lui seul peut la
            // rapporter. Elle reste sans valeur pour le serveur, qui la
            // ramène de toute façon à un simple hôte.
            'referrer_host' => Audience::normaliserProvenance(
                ($donnees['referrer'] ?? null) ?: $request->headers->get('referer')
            ),
            'device' => $this->appareil($agent),
            // Résolu par le garde, jamais déclaré par le client, qui n'aurait
            // aucune raison d'être cru sur ce point.
            'is_member' => auth('sanctum')->check(),
            // Nuls tant que l'accord n'est pas donné, et la mesure continue
            // sans eux : refuser ne rend pas invisible, cela retire seulement
            // le recoupement d'une page à l'autre.
            'visitor_id' => $donnees['visitor'] ?? null,
            'session_id' => $donnees['session'] ?? null,
            'visited_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    private function appareil(string $agent): string
    {
        if (preg_match(self::TABLETTE, $agent)) {
            return 'tablet';
        }

        if (preg_match(self::MOBILE, $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
