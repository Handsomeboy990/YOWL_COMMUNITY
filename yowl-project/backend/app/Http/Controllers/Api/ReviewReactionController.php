<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewReaction;
use App\Notifications\ReviewLiked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewReactionController extends Controller
{
    public function toggleReaction(Request $request, $id)
    {
        $request->validate([
            'reaction' => 'required|in:like,dislike',
        ]);

        $review = Review::findOrFail($id);
        $userId = Auth::id();
        $reactionType = $request->reaction;

        // Lecture puis ecriture dans une transaction : deux clics simultanes
        // creaient deux lignes et laissaient les compteurs faux. La contrainte
        // d'unicite en base ferme le dernier interstice.
        [$userReaction, $isNewLike] = DB::transaction(function () use ($id, $userId, $reactionType, $review) {
            $existing = ReviewReaction::where('review_id', $id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            $newLike = false;
            $result = $reactionType;

            if ($existing && $existing->reaction === $reactionType) {
                // Cliquer deux fois sur la meme reaction la retire
                $existing->delete();
                $result = null;
            } elseif ($existing) {
                $existing->update(['reaction' => $reactionType]);
            } else {
                ReviewReaction::create([
                    'review_id' => $id,
                    'user_id' => $userId,
                    'reaction' => $reactionType,
                ]);
                $newLike = $reactionType === 'like';
            }

            $counts = ReviewReaction::where('review_id', $id)
                ->selectRaw("SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) AS likes")
                ->selectRaw("SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) AS dislikes")
                ->first();

            $review->nb_like = (int) ($counts->likes ?? 0);
            $review->nb_dislike = (int) ($counts->dislikes ?? 0);
            $review->score = \App\Support\FeedScore::for($review);
            $review->save();

            return [$result, $newLike];
        });

        // Notifier l'auteur de la review quand quelqu'un ajoute un "j'aime"
        if ($isNewLike && $review->user_id !== $userId && $review->user) {
            $review->user->notify(new ReviewLiked(Auth::user(), $review));
        }

        return response()->json([
            'nb_like' => $review->nb_like,
            'nb_dislike' => $review->nb_dislike,
            'user_reaction' => $userReaction,
        ]);
    }
}
