<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Review;
use App\Notifications\CommentReceived;
use App\Notifications\ReplyReceived;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Restrict a comment query to content the caller is allowed to read.
     *
     * A comment inherits the visibility of the review it hangs from. Without
     * this, a review pulled from the feed by moderation stayed readable
     * through its comments, which are served on a public route.
     */
    private function visibleTo(Builder $query, $user): Builder
    {
        return $query->whereHas('review', function ($reviewQuery) use ($user) {
            $reviewQuery->where('is_published', true);
            if ($user) {
                $reviewQuery->orWhere('user_id', $user->id);
            }
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = auth('sanctum')->user();

        $query = $this->visibleTo(
            Comment::with(['user:id,username,picture', 'review:id,user_id']),
            $currentUser
        );

        // Bloquer quelqu'un doit aussi faire taire ses commentaires.
        if ($currentUser) {
            $query->whereNotIn(
                'user_id',
                \App\Models\Block::where('user_id', $currentUser->id)->select('blocked_id')
            );
        }

        $comments = $query->orderByDesc('created_at')->paginate(10);

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

        $review = Review::find($validated['review_id']);
        $author = $request->user();

        // On ne commente pas une review retiree du fil, sauf la sienne.
        if (! $review->is_published && $review->user_id !== $author->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette review n\'accepte plus de commentaires.',
            ], 403);
        }

        // Une reponse appartient forcement a la meme review que le commentaire
        // auquel elle repond : sans ce controle, un fil de discussion pouvait
        // etre greffe sur une review etrangere.
        if (! empty($validated['parent_id'])) {
            $parentReviewId = Comment::whereKey($validated['parent_id'])->value('review_id');
            if ((int) $parentReviewId !== (int) $validated['review_id']) {
                return response()->json([
                    'success' => false,
                    'error' => ['parent_id' => ['Le commentaire parent appartient à une autre review.']],
                    'message' => 'Réponse invalide.',
                ], 422);
            }
        }

        $validated['user_id'] = $author->id;
        $comment = Comment::create($validated);

        // Un commentaire pese dans le classement du fil.
        \App\Support\FeedScore::refresh($review);

        // Notifier l'auteur de la review et celui du commentaire parent
        if ($review && $review->user_id !== $author->id && $review->user) {
            $review->user->notify(new CommentReceived($author, $comment));
        }
        // Mentions : @pseudo previent la personne citee.
        foreach (\App\Support\Mentions::resolve($comment->content, $author) as $mentioned) {
            if ($mentioned->id !== $review?->user_id) {
                $mentioned->notify(new \App\Notifications\Mentioned($author, $comment->review_id, 'commentaire'));
            }
        }

        if ($comment->parent_id) {
            $parent = Comment::find($comment->parent_id);
            if ($parent && $parent->user_id !== $author->id && $parent->user_id !== $review?->user_id && $parent->user) {
                $parent->user->notify(new ReplyReceived($author, $comment));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $comment->load(['user']),
            'message' => 'Commentaire créé avec succès',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        $currentUser = auth('sanctum')->user();
        $review = $comment->review;

        // Meme regle que pour la liste : un commentaire accroche a une review
        // depubliee n'est lisible que par l'auteur de cette review.
        if (! $review || (! $review->is_published && (! $currentUser || $currentUser->id !== $review->user_id))) {
            return response()->json([
                'success' => false,
                'message' => 'Commentaire introuvable.',
            ], 404);
        }

        $comment->load(['user:id,username,picture', 'review:id,user_id']);

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
