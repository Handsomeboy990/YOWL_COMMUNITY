<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Notifications\CommentLiked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentReactionController extends Controller
{
    public function toggleReaction(Request $request, $id)
    {
        $request->validate([
            'reaction' => 'required|in:like,dislike',
        ]);

        $comment = Comment::findOrFail($id);
        $userId = Auth::id();
        $reactionType = $request->reaction;

        // Meme protection que sur les reviews : lecture verrouillee, ecriture
        // et recalcul des compteurs dans une seule transaction.
        [$userReaction, $isNewLike] = DB::transaction(function () use ($id, $userId, $reactionType, $comment) {
            $existing = CommentReaction::where('comment_id', $id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            $newLike = false;
            $result = $reactionType;

            if ($existing && $existing->reaction === $reactionType) {
                $existing->delete();
                $result = null;
            } elseif ($existing) {
                $existing->update(['reaction' => $reactionType]);
            } else {
                CommentReaction::create([
                    'comment_id' => $id,
                    'user_id' => $userId,
                    'reaction' => $reactionType,
                ]);
                $newLike = $reactionType === 'like';
            }

            $counts = CommentReaction::where('comment_id', $id)
                ->selectRaw("SUM(CASE WHEN reaction = 'like' THEN 1 ELSE 0 END) AS likes")
                ->selectRaw("SUM(CASE WHEN reaction = 'dislike' THEN 1 ELSE 0 END) AS dislikes")
                ->first();

            $comment->nb_like = (int) ($counts->likes ?? 0);
            $comment->nb_dislike = (int) ($counts->dislikes ?? 0);
            $comment->save();

            return [$result, $newLike];
        });

        if ($isNewLike && $comment->user_id !== $userId && $comment->user) {
            $comment->user->notify(new CommentLiked(Auth::user(), $comment));
        }

        return response()->json([
            'nb_like' => $comment->nb_like,
            'nb_dislike' => $comment->nb_dislike,
            'user_reaction' => $userReaction,
        ]);
    }
}
