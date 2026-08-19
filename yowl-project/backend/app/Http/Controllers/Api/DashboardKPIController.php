<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardKPIController extends Controller
{
    /**
     * Contiguous partition of the 13 to 35 bracket, as [inclusive, exclusive].
     */
    private const AGE_RANGES = [
        '13-17' => [13, 18],
        '18-21' => [18, 22],
        '22-25' => [22, 26],
        '26-29' => [26, 30],
        '30-35' => [30, 36],
    ];

    /**
     * How long the counters are served from cache.
     *
     * The endpoint is public, unauthenticated and called on every feed load,
     * so it is the cheapest thing on the API to hammer. Community counters do
     * not need to be accurate to the second.
     */
    private const CACHE_SECONDS = 300;

    public function getKPI()
    {
        $kpi = Cache::remember('kpi.community', self::CACHE_SECONDS, fn () => $this->compute());

        return response()->json([
            'success' => true,
            'data' => $kpi,
            'message' => 'KPI successfully retrieved.',
        ], 200);
    }

    /**
     * Compute the counters in the database.
     *
     * The age partition used to be built by loading every member's birthdate
     * into memory and filtering the collection five times, which grew linearly
     * with the member count on a route anybody can call.
     */
    private function compute(): array
    {
        $totalReviews = Review::count();
        $activeDays = (int) Review::selectRaw('COUNT(DISTINCT DATE(created_at)) as days')->value('days');

        return [
            // Les comptes supprimes ne comptent plus dans la communaute.
            'nbUsers' => User::whereNull('anonymized_at')->count(),
            'nbReviews' => $totalReviews,
            'nbComments' => Comment::count(),
            'nbUsersByAgeRange' => $this->usersByAgeRange(),
            'nbMeanReviewsPerDay' => $activeDays === 0 ? 0 : round($totalReviews / $activeDays, 2),
        ];
    }

    /**
     * One aggregate query, with the bracket boundaries turned into dates.
     *
     * Comparing birthdates against precomputed dates keeps the expression
     * portable across SQLite, PostgreSQL and MySQL, and lets an index on
     * birthdate do the work.
     *
     * @return array<string, int>
     */
    private function usersByAgeRange(): array
    {
        $selects = [];
        $bindings = [];

        foreach (self::AGE_RANGES as $label => [$from, $to]) {
            $alias = 'r'.str_replace('-', '_', $label);
            $selects[] = "SUM(CASE WHEN birthdate > ? AND birthdate <= ? THEN 1 ELSE 0 END) AS {$alias}";
            $bindings[] = now()->subYears($to)->toDateString();
            $bindings[] = now()->subYears($from)->toDateString();
        }

        $row = User::whereNotNull('birthdate')
            ->whereNull('anonymized_at')
            ->selectRaw(implode(', ', $selects), $bindings)
            ->first();

        $partitions = [];
        foreach (array_keys(self::AGE_RANGES) as $label) {
            $alias = 'r'.str_replace('-', '_', $label);
            $partitions[$label] = (int) ($row?->{$alias} ?? 0);
        }

        return $partitions;
    }
}
