<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Audience;
use App\Support\Cached;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * L'audience du site, pour la console d'administration.
 *
 * Growth répond à « la communauté grandit-elle ». Ceci répond à « qui arrive,
 * par où, sur quoi ». Les deux vivent séparément parce qu'ils se lisent à des
 * rythmes différents et qu'aucun ne se déduit de l'autre.
 */
class AnalyticsController extends Controller
{
    /** Fenêtres proposées, en jours. Bornées pour que la requête reste lisible. */
    private const FENETRES = [7, 30, 90];

    public function index(Request $request): JsonResponse
    {
        $jours = (int) $request->query('jours', 30);
        if (! in_array($jours, self::FENETRES, true)) {
            $jours = 30;
        }

        // La clé de cache porte la fenêtre : sans elle, ouvrir sept jours
        // après trente rendrait les chiffres de trente.
        $donnees = Cached::remember(Cached::AUDIENCE.'.'.$jours, fn () => [
            'fenetre' => $jours,
            'total' => Audience::total($jours),
            'par_jour' => Audience::perDay($jours),
            'pages' => Audience::topPages($jours),
            'provenances' => Audience::topReferrers($jours),
            'appareils' => Audience::devices($jours),
            'contenus' => Audience::topContent(),
            'mesure_depuis' => Audience::mesureDepuis(),
            'calcule_le' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $donnees,
            'message' => 'Audience metrics retrieved successfully.',
        ]);
    }

    /**
     * Les mêmes chiffres en tableur, une section par thème.
     */
    public function export(Request $request): StreamedResponse
    {
        $jours = (int) $request->query('jours', 30);
        if (! in_array($jours, self::FENETRES, true)) {
            $jours = 30;
        }

        $nom = 'yowl-audience-'.$jours.'j-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($jours) {
            $sortie = fopen('php://output', 'w');

            // Séparateur explicite : sans lui, Excel en configuration
            // française ouvre le fichier sur une seule colonne.
            fwrite($sortie, "sep=,\n");

            fputcsv($sortie, ['Visites par jour']);
            fputcsv($sortie, ['Jour', 'Membres', 'Visiteurs']);
            foreach (Audience::perDay($jours) as $ligne) {
                fputcsv($sortie, [$ligne['jour'], $ligne['membres'], $ligne['visiteurs']]);
            }

            fputcsv($sortie, []);
            fputcsv($sortie, ['Pages']);
            fputcsv($sortie, ['Page', 'Visites', 'Part (%)']);
            foreach (Audience::topPages($jours) as $ligne) {
                fputcsv($sortie, [$ligne['page'], $ligne['visites'], $ligne['part']]);
            }

            $provenances = Audience::topReferrers($jours);
            fputcsv($sortie, []);
            fputcsv($sortie, ['Provenances']);
            fputcsv($sortie, ['Source', 'Visites']);
            fputcsv($sortie, ['Accès direct', $provenances['direct']]);
            foreach ($provenances['sources'] as $ligne) {
                fputcsv($sortie, [$ligne['hote'], $ligne['visites']]);
            }

            fputcsv($sortie, []);
            fputcsv($sortie, ['Appareils']);
            fputcsv($sortie, ['Appareil', 'Visites', 'Part (%)']);
            foreach (Audience::devices($jours) as $ligne) {
                fputcsv($sortie, [$ligne['appareil'], $ligne['visites'], $ligne['part']]);
            }

            fclose($sortie);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
