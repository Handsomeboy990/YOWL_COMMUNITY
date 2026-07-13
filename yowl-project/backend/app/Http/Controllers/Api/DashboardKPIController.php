<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;

class DashboardKPIController extends Controller
{
    public function getKPI()
    {

        $users = User::select('birthdate')
            ->whereNotNull('birthdate')
            ->get()
            ->map(fn ($user) => Carbon::parse($user->birthdate)->age);

        // Partition contiguë de la tranche 13-35 ans
        $partitions = [
            '13-17' => $users->filter(fn ($age) => $age >= 13 && $age < 18)->count(),
            '18-21' => $users->filter(fn ($age) => $age >= 18 && $age < 22)->count(),
            '22-25' => $users->filter(fn ($age) => $age >= 22 && $age < 26)->count(),
            '26-29' => $users->filter(fn ($age) => $age >= 26 && $age < 30)->count(),
            '30-35' => $users->filter(fn ($age) => $age >= 30 && $age <= 35)->count(),
        ];

        // Moyenne de reviews par jour d'activité (agrégée en base)
        $totalCountReviews = Review::count();
        $nbDays = Review::selectRaw("COUNT(DISTINCT DATE(created_at)) as days")->value('days');
        $mean = $nbDays == 0 ? 0 : $totalCountReviews / $nbDays;

        $KPI = [
            'nbUsers' => User::count(),
            'nbReviews' => Review::count(),
            'nbComments' => Comment::count(),
            'nbUsersByAgeRange' => $partitions,
            'nbMeanReviewsPerDay' => round($mean, 2),
        ];

        return response()->json([
            'success' => true,
            'data' => $KPI,
            'message' => 'KPI successfully retrieved.',
        ], 200);
    }

}
