<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardKPIController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    public function getKPI()
    {

        $users = User::select('birthdate')->get()->map(fn ($user) => Carbon::parse($user->birthdate)->age);

        $partitions = [
            '13-14' => $users->sum(fn ($age) => $age >= 13 && $age < 14),
            '14-18' => $users->sum(fn ($age) => $age >= 14 && $age < 18),
            '18-22' => $users->sum(fn ($age) => $age >= 18 && $age < 22),
            '22-26' => $users->sum(fn ($age) => $age >= 22 && $age < 26),
            '26-30' => $users->sum(fn ($age) => $age >= 26 && $age < 30),
            '30-34' => $users->sum(fn ($age) => $age >= 30 && $age < 34),
            '34-35' => $users->sum(fn ($age) => $age >= 34 && $age <= 35),
        ];

        $reviews = Review::orderBy('created_at')->get()->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        });

        $totalCountReviews = 0;
        $nbDays = 0;
        foreach ($reviews as $key => $post) {
            $nbDays += 1;
            $totalCountReviews += $post->count();
        }
        $mean = $nbDays == 0? 0 : $totalCountReviews / $nbDays ;

        $KPI = [
            'nbUsers' => User::all()->count(),
            'nbReviews' => Review::all()->count(),
            'nbComments' => Comment::all()->count(),
            'nbUsersByAgeRange' => $partitions,
            'nbMeanReviewsPerDay' => $mean,
        ];

        return response()->json([
            'success' => true,
            'data' => $KPI,
            'message' => 'KPI successfully retrieved.',
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
