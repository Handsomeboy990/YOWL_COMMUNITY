<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Charge tous les commentaire avec leurs relations (auteur, review, et reponse enfant)
        $comments = Comment::with(['user', 'review', 'children'])->orderByDesc('created_at')->paginate(10);

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
        //
        $comment->load(['user', 'review', 'children']);

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Commentaire recupere avec succes',
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
                'message' => 'Action non autorisee',
            ], 403);
        }
        $validated = $request->validate([
            'content' => 'sometimes|required|string',
        ]);
        $comment->update($validated);

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Commentaire mis a jour avec succes',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comment $comment)
    {
        // Verifier si c'est bien l'auteur
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisee',
            ], 403);
        }
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprime avec succes',
        ], 204);
    }
}
