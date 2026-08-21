<?php

namespace App\Support;

use App\Models\PageVisit;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mesure d'audience : ce que le site reçoit, par opposition à ce que la
 * communauté produit, qui relève de Growth.
 *
 * Une limite est assumée et affichée telle quelle dans le tableau de bord :
 * faute d'identifiant de visiteur, on compte des visites, jamais des
 * visiteurs uniques. Distinguer les deux demanderait de suivre les gens
 * entre deux pages, c'est à dire exactement ce que cette mesure refuse de
 * faire. Une courbe intitulée « visiteurs » qui compterait des visites
 * serait un chiffre faux affiché avec assurance.
 */
class Audience
{
    /**
     * Les motifs de route que l'on accepte de compter.
     *
     * Cette liste vit ici, et non côté navigateur, pour deux raisons. Elle
     * borne la cardinalité : sans elle, n'importe qui peut poster dix mille
     * chemins distincts et rendre le classement des pages illisible. Et elle
     * protège la vie privée : « /membres/handsomeboy » dirait quel profil a
     * été consulté, « /membres/:username » ne dit rien de tel.
     *
     * Elle doit suivre le routeur du frontend. Un chemin inconnu est compté
     * sous « autre » plutôt que rejeté : mieux vaut une ligne agrégée qu'un
     * trou dans la mesure.
     */
    private const MOTIFS = [
        '/',
        '/feed',
        '/feed/:page',
        '/login',
        '/signup',
        '/forgot-password',
        '/password-reset/:token',
        '/reviews/:id',
        '/reviews/:id/:page',
        '/share',
        '/sujets',
        '/sujets/:name',
        '/membres/:username',
        '/bienvenue',
        '/user/summary',
        '/user/activity',
        '/user/saved',
        '/user/my-reviews',
        '/user/contestations',
        '/about',
        '/faq',
        '/charte',
        '/policy',
        '/confidentialite',
        '/conditions',
        '/mentions-legales',
        '/desinscription/:token',
        '/admin',
    ];

    public const AUTRE = 'autre';

    /** Les trois familles d'appareil, dans l'ordre où le tableau les montre. */
    public const APPAREILS = ['mobile', 'tablet', 'desktop'];

    /**
     * Ramène une URL de navigation au motif de route correspondant.
     *
     * La chaîne reçue vient du navigateur, donc de nulle part : elle est
     * traitée comme une saisie hostile jusqu'à correspondre à un motif connu.
     */
    public static function normaliserChemin(string $brut): string
    {
        // La requête et le fragment ne sont jamais conservés : ils portent
        // les termes de recherche et parfois un jeton.
        $chemin = Str::before(Str::before($brut, '?'), '#');
        $chemin = '/'.trim($chemin, '/');
        $chemin = Str::lower($chemin);

        if ($chemin === '/') {
            return '/';
        }

        $segments = explode('/', trim($chemin, '/'));

        foreach (self::MOTIFS as $motif) {
            if ($motif === '/') {
                continue;
            }

            $attendus = explode('/', trim($motif, '/'));

            if (count($attendus) !== count($segments)) {
                continue;
            }

            $correspond = true;
            foreach ($attendus as $i => $attendu) {
                // Un segment variable accepte n'importe quoi, et c'est le
                // motif qui est stocké, pas la valeur.
                if (str_starts_with($attendu, ':')) {
                    continue;
                }
                if ($attendu !== $segments[$i]) {
                    $correspond = false;
                    break;
                }
            }

            if ($correspond) {
                return $motif;
            }
        }

        return self::AUTRE;
    }

    /**
     * Ramène un référent à son seul hôte.
     *
     * Le chemin d'une page de provenance porte souvent la requête tapée, et
     * parfois l'identifiant de la personne sur l'autre site. Seul l'hôte est
     * conservé. Une provenance interne ne dit rien et n'est pas comptée.
     */
    public static function normaliserProvenance(?string $brut): ?string
    {
        if (! $brut) {
            return null;
        }

        $hote = parse_url($brut, PHP_URL_HOST);

        if (! $hote) {
            return null;
        }

        $hote = Str::lower(Str::replaceStart('www.', '', $hote));

        $interne = parse_url((string) config('app.frontend_url'), PHP_URL_HOST);
        if ($interne && $hote === Str::lower(Str::replaceStart('www.', '', $interne))) {
            return null;
        }

        return Str::limit($hote, 120, '');
    }

    /**
     * Les visites jour par jour, membres et visiteurs séparés.
     *
     * Les jours sans visite sont rendus à zéro plutôt qu'absents : une courbe
     * qui saute les jours creux dessine une fréquentation plus régulière
     * qu'elle ne l'est.
     *
     * @return array<int, array{jour: string, libelle: string, membres: int, visiteurs: int}>
     */
    public static function perDay(int $days = 30): array
    {
        $depuis = CarbonImmutable::now()->subDays($days - 1)->startOfDay();

        $lignes = PageVisit::query()
            ->where('visited_at', '>=', $depuis)
            ->selectRaw('date(visited_at) as jour, is_member, count(*) as total')
            ->groupBy('jour', 'is_member')
            ->get()
            ->groupBy('jour');

        $serie = [];
        for ($i = 0; $i < $days; $i++) {
            $jour = $depuis->addDays($i);
            $cle = $jour->format('Y-m-d');
            $duJour = $lignes->get($cle) ?? collect();

            $serie[] = [
                'jour' => $cle,
                'libelle' => $jour->translatedFormat('d M'),
                'membres' => (int) ($duJour->firstWhere('is_member', true)->total ?? 0),
                'visiteurs' => (int) ($duJour->firstWhere('is_member', false)->total ?? 0),
            ];
        }

        return $serie;
    }

    /**
     * Les pages les plus consultées, en motifs de route.
     *
     * @return array<int, array{page: string, visites: int, part: float}>
     */
    public static function topPages(int $days = 30, int $limit = 12): array
    {
        $depuis = CarbonImmutable::now()->subDays($days);
        $total = max(1, self::total($days));

        return PageVisit::query()
            ->where('visited_at', '>=', $depuis)
            ->select('path', DB::raw('count(*) as visites'))
            ->groupBy('path')
            ->orderByDesc('visites')
            ->limit($limit)
            ->get()
            ->map(fn ($l) => [
                'page' => $l->path,
                'visites' => (int) $l->visites,
                'part' => round($l->visites / $total * 100, 1),
            ])
            ->all();
    }

    /**
     * D'où viennent les arrivées, hôte par hôte.
     *
     * L'accès direct, celui sans référent, est compté à part : c'est
     * généralement la part la plus grosse et la fondre dans le reste
     * fausserait la lecture des autres.
     *
     * @return array{direct: int, sources: array<int, array{hote: string, visites: int}>}
     */
    public static function topReferrers(int $days = 30, int $limit = 10): array
    {
        $depuis = CarbonImmutable::now()->subDays($days);

        $direct = PageVisit::query()
            ->where('visited_at', '>=', $depuis)
            ->whereNull('referrer_host')
            ->count();

        $sources = PageVisit::query()
            ->where('visited_at', '>=', $depuis)
            ->whereNotNull('referrer_host')
            ->select('referrer_host', DB::raw('count(*) as visites'))
            ->groupBy('referrer_host')
            ->orderByDesc('visites')
            ->limit($limit)
            ->get()
            ->map(fn ($l) => ['hote' => $l->referrer_host, 'visites' => (int) $l->visites])
            ->all();

        return ['direct' => $direct, 'sources' => $sources];
    }

    /**
     * La répartition par famille d'appareil.
     *
     * @return array<int, array{appareil: string, visites: int, part: float}>
     */
    public static function devices(int $days = 30): array
    {
        $depuis = CarbonImmutable::now()->subDays($days);
        $total = max(1, self::total($days));

        $compte = PageVisit::query()
            ->where('visited_at', '>=', $depuis)
            ->select('device', DB::raw('count(*) as visites'))
            ->groupBy('device')
            ->pluck('visites', 'device');

        return collect(self::APPAREILS)
            ->map(fn ($appareil) => [
                'appareil' => $appareil,
                'visites' => (int) ($compte[$appareil] ?? 0),
                'part' => round(((int) ($compte[$appareil] ?? 0)) / $total * 100, 1),
            ])
            ->all();
    }

    /**
     * Les avis les plus vus, qui ne se déduisent pas des visites de page :
     * le compteur porté par chaque avis compte aussi les lectures dans le
     * fil, sans ouverture de la page dédiée.
     *
     * @return array<int, array{id: int, titre: string, vues: int, auteur: string|null}>
     */
    public static function topContent(int $limit = 10): array
    {
        return Review::query()
            ->where('is_published', true)
            ->with('user:id,username')
            ->orderByDesc('nb_views')
            ->limit($limit)
            ->get(['id', 'content', 'nb_views', 'user_id'])
            ->map(fn ($avis) => [
                'id' => $avis->id,
                'titre' => Str::limit(strip_tags((string) $avis->content), 70),
                'vues' => (int) $avis->nb_views,
                'auteur' => $avis->user?->username,
            ])
            ->all();
    }

    /**
     * Ce que l'accord a débloqué, et sur quelle part du trafic.
     *
     * Les trois mesures qui suivent ne portent que sur les visites munies
     * d'un identifiant, donc sur les personnes qui l'ont accepté. La part est
     * renvoyée avec elles, et le tableau de bord l'affiche : un nombre de
     * visiteurs uniques calculé sur un tiers du trafic n'est pas un nombre de
     * visiteurs uniques, et le présenter comme tel serait un chiffre faux.
     *
     * @return array{avec: int, sans: int, part: float}
     */
    public static function consentement(int $days = 30): array
    {
        $depuis = CarbonImmutable::now()->subDays($days);
        $base = PageVisit::where('visited_at', '>=', $depuis);

        $avec = (clone $base)->whereNotNull('visitor_id')->count();
        $total = (clone $base)->count();

        return [
            'avec' => $avec,
            'sans' => $total - $avec,
            'part' => $total > 0 ? round($avec / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Visiteurs distincts, nouveaux et revenants.
     *
     * Est nouveau celui dont la toute première visite tombe dans la fenêtre.
     * La comparaison se fait sur l'historique entier, pas sur la fenêtre :
     * quelqu'un venu il y a six mois et revenu hier est un revenant, même si
     * la fenêtre affichée ne montre que sa visite d'hier.
     *
     * @return array{uniques: int, nouveaux: int, revenants: int}
     */
    public static function visiteurs(int $days = 30): array
    {
        $depuis = CarbonImmutable::now()->subDays($days);

        $identifiants = PageVisit::query()
            ->where('visited_at', '>=', $depuis)
            ->whereNotNull('visitor_id')
            ->distinct()
            ->pluck('visitor_id');

        if ($identifiants->isEmpty()) {
            return ['uniques' => 0, 'nouveaux' => 0, 'revenants' => 0];
        }

        // Une seule requête pour les premières venues, par paquets : la liste
        // des identifiants peut être longue et certains moteurs bornent le
        // nombre de paramètres d'un IN.
        $nouveaux = 0;
        foreach ($identifiants->chunk(500) as $lot) {
            $nouveaux += PageVisit::query()
                ->whereIn('visitor_id', $lot)
                ->groupBy('visitor_id')
                ->havingRaw('min(visited_at) >= ?', [$depuis])
                ->get(['visitor_id'])
                ->count();
        }

        return [
            'uniques' => $identifiants->count(),
            'nouveaux' => $nouveaux,
            'revenants' => $identifiants->count() - $nouveaux,
        ];
    }

    /**
     * Pages par session, et part des sessions d'une seule page.
     *
     * Une session s'arrête après trente minutes sans page ouverte ; la coupe
     * est faite par le navigateur, qui seul sait quand la personne a cessé de
     * naviguer. Le taux d'une seule page est ce que d'autres outils appellent
     * le rebond : arrivé, reparti sans rien ouvrir d'autre.
     *
     * @return array{moyenne: float, sessions: int, une_seule_page: float}
     */
    public static function sessions(int $days = 30): array
    {
        $depuis = CarbonImmutable::now()->subDays($days);

        $parSession = PageVisit::query()
            ->where('visited_at', '>=', $depuis)
            ->whereNotNull('session_id')
            ->select('session_id', DB::raw('count(*) as pages'))
            ->groupBy('session_id')
            ->pluck('pages');

        if ($parSession->isEmpty()) {
            return ['moyenne' => 0.0, 'sessions' => 0, 'une_seule_page' => 0.0];
        }

        $seules = $parSession->filter(fn ($n) => (int) $n === 1)->count();

        return [
            'moyenne' => round($parSession->sum() / $parSession->count(), 2),
            'sessions' => $parSession->count(),
            'une_seule_page' => round($seules / $parSession->count() * 100, 1),
        ];
    }

    /** Le total de visites sur la fenêtre, toutes pages confondues. */
    public static function total(int $days = 30): int
    {
        return PageVisit::where('visited_at', '>=', CarbonImmutable::now()->subDays($days))->count();
    }

    /**
     * La date de la première visite enregistrée.
     *
     * Le tableau de bord s'en sert pour dire depuis quand il mesure : une
     * courbe à plat sur trente jours se lit autrement selon que la mesure
     * existe depuis un mois ou depuis avant-hier.
     */
    public static function mesureDepuis(): ?string
    {
        $premiere = PageVisit::min('visited_at');

        return $premiere ? CarbonImmutable::parse($premiere)->toIso8601String() : null;
    }
}
