<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $existingReaction = ReviewReaction::where('review_id', $id)
            ->where('user_id', $userId)
            ->first();

        $userReaction = $reactionType;
        if ($existingReaction) {
            if ($existingReaction->reaction === $reactionType) {
                // Cliquer deux fois sur la même réaction la retire
                $existingReaction->delete();
                $userReaction = null;
            } else {
                $existingReaction->update(['reaction' => $reactionType]);
            }
        } else {
            // Nouvelle réaction
            ReviewReaction::create([
                'review_id' => $id,
                'user_id' => $userId,
                'reaction' => $reactionType,
            ]);
        }

        // Recalcul des totaux
        $review->nb_like = ReviewReaction::where('review_id', $id)->where('reaction', 'like')->count();
        $review->nb_dislike = ReviewReaction::where('review_id', $id)->where('reaction', 'dislike')->count();
        $review->save();

        // Notifier l'auteur de la review quand quelqu'un ajoute un "j'aime"
        if ($userReaction === 'like' && ! $existingReaction && $review->user_id !== $userId) {
            app(\App\Services\PushNotificationService::class)->sendToUser(
                $review->user,
                'Nouvelle réaction',
                Auth::user()->username." a aimé ta review.",
                '/reviews/'.$review->id
            );
        }

        return response()->json([
            'nb_like' => $review->nb_like,
            'nb_dislike' => $review->nb_dislike,
            'user_reaction' => $userReaction,
        ]);
    }
}
