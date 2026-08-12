<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Notifications\CommentLiked;
use Illuminate\Support\Facades\Auth;


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

        $existingReaction = CommentReaction::where('comment_id', $id)
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
            CommentReaction::create([
                'comment_id' => $id,
                'user_id' => $userId,
                'reaction' => $reactionType,
            ]);
        }

        // Recalcul des totaux
        $comment->nb_like = CommentReaction::where('comment_id', $id)->where('reaction', 'like')->count();
        $comment->nb_dislike = CommentReaction::where('comment_id', $id)->where('reaction', 'dislike')->count();
        $comment->save();

        // Notifier l'auteur du commentaire quand quelqu'un ajoute un "j'aime"
        if ($userReaction === 'like' && ! $existingReaction && $comment->user_id !== $userId && $comment->user) {
            $comment->user->notify(new CommentLiked(Auth::user(), $comment));
        }

        return response()->json([
            'nb_like' => $comment->nb_like,
            'nb_dislike' => $comment->nb_dislike,
            'user_reaction' => $userReaction,
        ]);
    }
}
