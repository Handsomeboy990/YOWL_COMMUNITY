<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityPing;
use App\Support\Growth;
use Illuminate\Http\Request;
use App\Support\Cached;

class GrowthController extends Controller
{
    /**
     * The five indicators, plus signups per week.
     *
     * Durée de vie déclarée dans Cached, comme toutes les autres entrées :
     * un contrôleur qui appelle le cache directement finit par avoir sa propre
     * clé, sa propre durée et aucune invalidation.
     */
    public function index()
    {
        $donnees = Cached::remember(Cached::GROWTH, fn () => [
            'mau' => Growth::monthlyActive(),
            'inscriptions' => Growth::signupsPerWeek(),
            'commentaires' => Growth::commentsPerMember(),
            'engagement' => Growth::engagement(),
            'sessions' => Growth::sessions(),
            'retention' => Growth::retention(),
            'calcule_le' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $donnees,
            'message' => 'Growth metrics retrieved successfully.',
        ]);
    }

    /**
     * The same figures as a spreadsheet, one section per indicator.
     */
    public function export()
    {
        $donnees = [
            'mau' => Growth::monthlyActive(),
            'inscriptions' => Growth::signupsPerWeek(),
            'retention' => Growth::retention(),
        ];

        $lignes = [];
        $lignes[] = ['Membres actifs mensuels'];
        $lignes[] = ['Mois', 'Actifs'];
        foreach ($donnees['mau'] as $point) {
            $lignes[] = [$point['mois'], $point['actifs']];
        }

        $lignes[] = [];
        $lignes[] = ['Inscriptions par semaine'];
        $lignes[] = ['Semaine', 'Inscriptions'];
        foreach ($donnees['inscriptions'] as $point) {
            $lignes[] = [$point['semaine'], $point['inscriptions']];
        }

        $lignes[] = [];
        $lignes[] = ['Retention par cohorte'];
        $lignes[] = ['Cohorte', 'Membres', 'D1 %', 'D7 %', 'D30 %'];
        foreach ($donnees['retention'] as $ligne) {
            $lignes[] = [
                $ligne['cohorte'], $ligne['membres'],
                $ligne['d1'] ?? '', $ligne['d7'] ?? '', $ligne['d30'] ?? '',
            ];
        }

        $sortie = fopen('php://temp', 'r+');
        // Le BOM fait ouvrir le fichier en UTF-8 par les tableurs, qui sinon
        // supposent la page de code du systeme et cassent les accents.
        fwrite($sortie, "\xEF\xBB\xBF");
        foreach ($lignes as $ligne) {
            fputcsv($sortie, $ligne, ';');
        }
        rewind($sortie);
        $csv = stream_get_contents($sortie);
        fclose($sortie);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="yowl-croissance-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Presence signal, sent by the open tab.
     *
     * Written at most once a minute per member: the client cadence is not
     * trusted, and a burst of pings would inflate nothing but the table.
     */
    public function ping(Request $request)
    {
        $user = $request->user();
        $minute = now()->startOfMinute();

        $dejaVu = ActivityPing::where('user_id', $user->id)
            ->where('pinged_at', '>=', $minute)
            ->exists();

        if (! $dejaVu) {
            ActivityPing::create(['user_id' => $user->id, 'pinged_at' => $minute]);
        }

        return response()->json(['success' => true, 'message' => 'Presence recorded.']);
    }
}
