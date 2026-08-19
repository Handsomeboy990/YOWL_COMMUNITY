<?php

namespace App\Support;

use App\Models\ActivityPing;
use App\Models\Comment;
use App\Models\Review;
use App\Models\ReviewReaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The five indicators the specification promised, computed from the tables.
 *
 * Each one is defined here rather than in the controller because a growth
 * figure is only worth reading if its definition is fixed: "engagement rate"
 * means nothing until somebody writes down which numerator over which
 * denominator.
 */
class Growth
{
    /** Silence longer than this closes a session. */
    private const SESSION_GAP_MINUTES = 30;

    /** Cadence du signal de presence, ajoutee a la derniere minute vue. */
    private const PING_MINUTES = 1;

    /**
     * Monthly active members, month by month.
     *
     * Active means having done something: a ping, an avis, a commentaire or
     * une reaction. Restricting it to publication would count authors only
     * and call the rest of the community absent.
     */
    public static function monthlyActive(int $months = 12): array
    {
        $depuis = now()->startOfMonth()->subMonths($months - 1);
        $serie = [];

        for ($i = 0; $i < $months; $i++) {
            $debut = (clone $depuis)->addMonths($i);
            $fin = (clone $debut)->endOfMonth();

            $serie[] = [
                'mois' => $debut->format('Y-m'),
                'libelle' => $debut->translatedFormat('M Y'),
                'actifs' => self::activeBetween($debut, $fin),
            ];
        }

        return $serie;
    }

    /**
     * New members per week, the figure that says whether the door still opens.
     */
    public static function signupsPerWeek(int $weeks = 12): array
    {
        $depuis = now()->startOfWeek()->subWeeks($weeks - 1);
        $serie = [];

        for ($i = 0; $i < $weeks; $i++) {
            $debut = (clone $depuis)->addWeeks($i);
            $fin = (clone $debut)->endOfWeek();

            $serie[] = [
                'semaine' => $debut->format('Y-\WW'),
                'libelle' => $debut->translatedFormat('d M'),
                'inscriptions' => User::whereNull('anonymized_at')
                    ->whereBetween('created_at', [$debut, $fin])->count(),
            ];
        }

        return $serie;
    }

    /**
     * Average comments per member, and how the community splits around it.
     *
     * The average alone hides the shape: ten members writing a hundred
     * comments each and a thousand writing none give the same average as an
     * evenly talkative community, and the two need opposite decisions.
     */
    public static function commentsPerMember(): array
    {
        $membres = User::whereNull('anonymized_at')->count();
        $commentaires = Comment::count();

        $parMembre = DB::table('comments')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $tranches = ['0' => 0, '1-4' => 0, '5-19' => 0, '20-49' => 0, '50+' => 0];
        $silencieux = $membres - $parMembre->count();
        $tranches['0'] = max(0, $silencieux);

        foreach ($parMembre as $total) {
            $tranches[match (true) {
                $total < 5 => '1-4',
                $total < 20 => '5-19',
                $total < 50 => '20-49',
                default => '50+',
            }]++;
        }

        return [
            'moyenne' => $membres > 0 ? round($commentaires / $membres, 2) : 0.0,
            'mediane' => self::median($parMembre->values()->all(), $silencieux),
            'repartition' => $tranches,
        ];
    }

    /**
     * Engagement rate: reactions, comments and bookmarks against the reach.
     *
     * The denominator is the number of views, so the figure answers "of the
     * people who saw something, how many did anything about it", which is
     * the only reading that survives a change in audience size.
     */
    public static function engagement(int $days = 30): array
    {
        $depuis = now()->subDays($days);

        $reactions = ReviewReaction::where('created_at', '>=', $depuis)->count();
        $commentaires = Comment::where('created_at', '>=', $depuis)->count();
        $enregistrements = DB::table('bookmarks')->where('created_at', '>=', $depuis)->count();
        $utiles = DB::table('helpful_votes')->where('created_at', '>=', $depuis)->count();

        $vues = (int) Review::where('created_at', '>=', $depuis)->sum('nb_views');
        $interactions = $reactions + $commentaires + $enregistrements + $utiles;

        return [
            'taux' => $vues > 0 ? round($interactions / $vues * 100, 2) : 0.0,
            'vues' => $vues,
            'interactions' => $interactions,
            'repartition' => [
                'reactions' => $reactions,
                'commentaires' => $commentaires,
                'enregistrements' => $enregistrements,
                'utiles' => $utiles,
            ],
        ];
    }

    /**
     * Average session length, in minutes, from the presence pings.
     *
     * A session is a run of pings with no gap wider than the threshold. The
     * last ping of a run gets one interval added, otherwise a visit of a
     * single ping would measure zero minutes instead of about one.
     */
    public static function sessions(int $days = 30): array
    {
        $pings = ActivityPing::where('pinged_at', '>=', now()->subDays($days))
            ->orderBy('user_id')
            ->orderBy('pinged_at')
            ->get(['user_id', 'pinged_at']);

        if ($pings->isEmpty()) {
            return ['moyenne_minutes' => 0.0, 'sessions' => 0, 'mesure_depuis' => null];
        }

        $durees = [];
        $membreCourant = null;
        $debut = null;
        $dernier = null;

        foreach ($pings as $ping) {
            // diffInMinutes est signe : sans valeur absolue, un ecart vers le
            // futur ressort negatif et aucune session ne serait jamais coupee.
            $rupture = $ping->user_id !== $membreCourant
                || abs($ping->pinged_at->diffInMinutes($dernier)) > self::SESSION_GAP_MINUTES;

            if ($rupture) {
                if ($debut !== null) {
                    $durees[] = abs($dernier->diffInMinutes($debut)) + self::PING_MINUTES;
                }
                $membreCourant = $ping->user_id;
                $debut = $ping->pinged_at;
            }

            $dernier = $ping->pinged_at;
        }
        $durees[] = abs($dernier->diffInMinutes($debut)) + self::PING_MINUTES;

        return [
            'moyenne_minutes' => round(array_sum($durees) / count($durees), 1),
            'sessions' => count($durees),
            'mesure_depuis' => ActivityPing::min('pinged_at'),
        ];
    }

    /**
     * D1, D7 and D30 retention, per registration cohort.
     *
     * A cohort is only counted once its window has closed: a member who
     * registered yesterday cannot yet have failed D7, and including them
     * would drag every recent cohort towards zero.
     */
    public static function retention(int $cohorts = 8): array
    {
        $lignes = [];
        $debutSemaine = now()->startOfWeek()->subWeeks($cohorts - 1);

        for ($i = 0; $i < $cohorts; $i++) {
            $debut = (clone $debutSemaine)->addWeeks($i);
            $fin = (clone $debut)->endOfWeek();

            $membres = User::whereNull('anonymized_at')
                ->whereBetween('created_at', [$debut, $fin])
                ->get(['id', 'created_at']);

            if ($membres->isEmpty()) {
                continue;
            }

            $ligne = [
                'cohorte' => $debut->translatedFormat('d M'),
                'membres' => $membres->count(),
            ];

            foreach ([1, 7, 30] as $jour) {
                // La fenetre doit etre fermee pour que le chiffre veuille
                // dire quelque chose. Sinon on affiche null, pas zero.
                $ligne['d'.$jour] = $fin->copy()->addDays($jour)->isFuture()
                    ? null
                    : self::retainedAt($membres, $jour);
            }

            $lignes[] = $ligne;
        }

        return $lignes;
    }

    /**
     * Part of a cohort still there on the day following registration by N days.
     */
    private static function retainedAt($membres, int $jour): float
    {
        $revenus = 0;

        foreach ($membres as $membre) {
            $debut = $membre->created_at->copy()->addDays($jour)->startOfDay();
            $fin = (clone $debut)->endOfDay();

            if (self::userActiveBetween($membre->id, $debut, $fin)) {
                $revenus++;
            }
        }

        return round($revenus / $membres->count() * 100, 1);
    }

    private static function activeBetween(Carbon $debut, Carbon $fin): int
    {
        $ids = collect()
            ->merge(ActivityPing::whereBetween('pinged_at', [$debut, $fin])->distinct()->pluck('user_id'))
            ->merge(Review::whereBetween('created_at', [$debut, $fin])->distinct()->pluck('user_id'))
            ->merge(Comment::whereBetween('created_at', [$debut, $fin])->distinct()->pluck('user_id'))
            ->merge(ReviewReaction::whereBetween('created_at', [$debut, $fin])->distinct()->pluck('user_id'));

        return $ids->unique()->count();
    }

    private static function userActiveBetween(int $userId, Carbon $debut, Carbon $fin): bool
    {
        return ActivityPing::where('user_id', $userId)->whereBetween('pinged_at', [$debut, $fin])->exists()
            || Review::where('user_id', $userId)->whereBetween('created_at', [$debut, $fin])->exists()
            || Comment::where('user_id', $userId)->whereBetween('created_at', [$debut, $fin])->exists()
            || ReviewReaction::where('user_id', $userId)->whereBetween('created_at', [$debut, $fin])->exists();
    }

    /**
     * Median including the members who never commented, counted as zero.
     */
    private static function median(array $valeurs, int $zeros): float
    {
        $tous = array_merge(array_fill(0, max(0, $zeros), 0), $valeurs);
        if (! $tous) {
            return 0.0;
        }

        sort($tous);
        $milieu = intdiv(count($tous), 2);

        return count($tous) % 2 === 0
            ? ($tous[$milieu - 1] + $tous[$milieu]) / 2
            : (float) $tous[$milieu];
    }
}
