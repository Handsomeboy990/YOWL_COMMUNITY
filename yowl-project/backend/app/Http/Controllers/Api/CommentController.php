<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Notifications\CommentReceived;
use App\Notifications\ReplyReceived;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Charge tous les commentaires avec leurs relations (auteur, review et réponses enfants)
        $comments = Comment::with(['user:id,username,picture', 'review:id,user_id', 'children'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return $comments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'review_id' => 'required|exists:reviews,id',
            'parent_id' => 'nullable|exists:comments,id',
            'content' => 'required|string|min:1|max:1000',
        ]);
        
        $validated['user_id'] = $request->user()->id;
        $comment = Comment::create($validated);

        // Notifier l'auteur de la review et celui du commentaire parent
        $author = $request->user();
        $review = $comment->review;

        if ($review && $review->user_id !== $author->id && $review->user) {
            $review->user->notify(new CommentReceived($author, $comment));
        }
        if ($comment->parent_id) {
            $parent = Comment::find($comment->parent_id);
            if ($parent && $parent->user_id !== $author->id && $parent->user_id !== $review?->user_id && $parent->user) {
                $parent->user->notify(new ReplyReceived($author, $comment));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $comment->load(['user', 'children']),
            'message' => 'Commentaire créé avec succès',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        $comment->load(['user:id,username,picture', 'review:id,user_id', 'children']);

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Commentaire récupéré avec succès',
        ]);
    }

    /**
     * Update the specified resource in storage..
     */
    public function update(Request $request, Comment $comment)
    {
        // verifier si c'est bien l'auteur
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }
        $validated = $request->validate([
            'content' => 'sometimes|required|string|max:1000',
        ]);
        $comment->update($validated);

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Commentaire mis à jour avec succès',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comment $comment)
    {
        // Vérifier si c'est bien l'auteur
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprimé avec succès',
        ], 200);
    }
}
