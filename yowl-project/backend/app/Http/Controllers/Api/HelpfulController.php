<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelpfulVote;
use App\Models\Review;
use App\Support\FeedScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HelpfulController extends Controller
{
    /**
     * Record whether a review helped the reader, or clear that judgement.
     *
     * Same toggle semantics as the reaction: voting the same way twice removes
     * the vote, so there is one gesture to learn rather than two.
     */
    public function toggle(Request $request, Review $review)
    {
        $validated = $request->validate([
            'helpful' => 'required|boolean',
        ]);

        if ($review->user_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tu ne peux pas juger de l\'utilité de ton propre avis.',
            ], 422);
        }

        $userId = $request->user()->id;
        $wanted = (bool) $validated['helpful'];

        $result = DB::transaction(function () use ($review, $userId, $wanted) {
            $existing = HelpfulVote::where('review_id', $review->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            $current = $wanted;
            if ($existing && $existing->helpful === $wanted) {
                $existing->delete();
                $current = null;
            } elseif ($existing) {
                $existing->update(['helpful' => $wanted]);
            } else {
                HelpfulVote::create([
                    'user_id' => $userId,
                    'review_id' => $review->id,
                    'helpful' => $wanted,
                ]);
            }

            $counts = HelpfulVote::where('review_id', $review->id)
                ->selectRaw('SUM(CASE WHEN helpful = 1 THEN 1 ELSE 0 END) AS oui')
                ->selectRaw('SUM(CASE WHEN helpful = 0 THEN 1 ELSE 0 END) AS non')
                ->first();

            $review->nb_helpful = (int) ($counts->oui ?? 0);
            $review->nb_unhelpful = (int) ($counts->non ?? 0);
            $review->score = FeedScore::for($review);
            $review->save();

            return $current;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user_helpful' => $result,
                'nb_helpful' => $review->nb_helpful,
                'nb_unhelpful' => $review->nb_unhelpful,
            ],
            'message' => 'Merci pour ton retour',
        ]);
    }
}
