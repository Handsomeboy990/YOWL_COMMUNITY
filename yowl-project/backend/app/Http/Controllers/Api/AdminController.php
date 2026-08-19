<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Report;
use App\Models\Review;
use App\Models\Comment;
use App\Models\Suggestion;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Support\Media;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function changeUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,client',
        ]);
        
        // Empêcher un admin de se rétrograder lui-même
        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre rôle'
            ], 403);
        }
        
        $user->syncRoles([$validated['role']]);
        return response()->json([
            'success' => true,
            'message' => "Rôle mis à jour : {$validated['role']}"
        ]);
    }
    public function banUser(Request $request, User $user)
    {
        // Un admin ne peut pas se bannir lui-même
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas bannir votre propre compte',
            ], 403);
        }

        $user->is_active = false;
        $user->save();

        // Révoquer immédiatement toutes les sessions du banni
        $user->tokens()->delete();

        return response()->json(['success'=>true,'message'=>'User banned']);
    }

    public function unbanUser(User $user)
    {
        $user->is_active = true;
        $user->save();
        return response()->json(['success'=>true,'message'=>'User unbanned']);
    }

    public function publishReview(Review $review)
    {
        $review->is_published = true;
        $review->save();
        return response()->json(['success'=>true,'message'=>'Review published']);
    }

    public function unpublishReview(Review $review)
    {
        $review->is_published = false;
        $review->save();
        return response()->json(['success'=>true,'message'=>'Review unpublished']);
    }

    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => User::count(),
                'reviews' => Review::count(),
                'comments' => Comment::count(),
                'tags' => Tag::count(),
                'latest_reviews' => Review::latest()->take(5)->get(['id','content','created_at']),
            ]
        ]);
    }

    /**
     * Case insensitive contains, portable across the supported engines.
     *
     * A plain LIKE is case sensitive on PostgreSQL, which is what production
     * runs: searching "jean" there never matched "Jean".
     */
    private function whereContains($query, string $column, string $search): void
    {
        $query->whereRaw('LOWER('.$column.') LIKE ?', ['%'.mb_strtolower($search).'%']);
    }

    private function orWhereContains($query, string $column, string $search): void
    {
        $query->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.mb_strtolower($search).'%']);
    }

    public function users(Request $request)
    {
        $query = User::query();
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $this->whereContains($q, 'username', $search);
                $this->orWhereContains($q, 'email', $search);
                $this->orWhereContains($q, 'fullname', $search);
            });
        }
        $users = $query->orderByDesc('created_at')->paginate(20);
        // Ajoute les rôles à chaque utilisateur
        $users->getCollection()->transform(function ($user) {
            $user->roles = $user->getRoleNames();
            return $user;
        });
        return response()->json(['success'=>true,'data'=>$users]);
    }

    public function reviews(Request $request)
    {
        $query = Review::with('user');
        if ($search = $request->input('search')) {
            $this->whereContains($query, 'content', $search);
        }
        $reviews = $query->orderByDesc('created_at')->paginate(20);
        return response()->json(['success'=>true,'data'=>$reviews]);
    }

    public function comments(Request $request)
    {
        $query = Comment::with(['user','review']);
        if ($search = $request->input('search')) {
            $this->whereContains($query, 'content', $search);
        }
        $comments = $query->orderByDesc('created_at')->paginate(30);
        return response()->json(['success'=>true,'data'=>$comments]);
    }

    public function deleteReview(Review $review)
    {
        // Supprimer physiquement les médias associés
        if (is_array($review->medias)) {
            foreach ($review->medias as $mediaPath) {
                Media::delete($mediaPath);
            }
        }

        $review->delete();
        return response()->json(['success'=>true,'message'=>'Review deleted']);
    }

    public function deleteComment(Comment $comment)
    {
        $comment->delete();
        return response()->json(['success'=>true,'message'=>'Comment deleted']);
    }

    /**
     * The moderation queue, pending reports first.
     */
    public function reports(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Report::STATUSES)],
        ]);

        $query = Report::with(['user:id,username,picture', 'handler:id,username', 'reportable']);
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $reports = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reports,
            'pending_count' => Report::where('status', Report::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Close a report, optionally deleting the reported content in the same move.
     */
    public function resolveReport(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Report::STATUS_DISMISSED, Report::STATUS_ACTIONED])],
            'delete_content' => 'nullable|boolean',
        ]);

        if ($request->boolean('delete_content')) {
            $reportable = $report->reportable;
            if ($reportable instanceof Review) {
                $this->deleteReview($reportable);
            } elseif ($reportable instanceof Comment) {
                $reportable->delete();
            }

            // Le contenu ayant disparu, les autres signalements qui le visent
            // n'ont plus d'objet : ils sont clos dans le meme geste.
            Report::where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id)
                ->where('id', '!=', $report->id)
                ->where('status', Report::STATUS_PENDING)
                ->update([
                    'status' => Report::STATUS_ACTIONED,
                    'handled_by' => $request->user()->id,
                    'handled_at' => now(),
                ]);
        }

        $report->status = $validated['status'];
        $report->handled_by = $request->user()->id;
        $report->handled_at = now();
        $report->save();

        return response()->json([
            'success' => true,
            'data' => $report->fresh(['user:id,username', 'handler:id,username']),
            'message' => 'Signalement traité',
        ]);
    }

    /**
     * The suggestions sent through the public form, newest first.
     */
    public function suggestions(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(Suggestion::STATUSES)],
        ]);

        $query = Suggestion::with(['user:id,username,picture', 'handler:id,username']);
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $suggestions = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
            'new_count' => Suggestion::where('status', Suggestion::STATUS_NEW)->count(),
        ]);
    }

    /**
     * Move a suggestion along its lifecycle: new, read, archived.
     */
    public function updateSuggestion(Request $request, Suggestion $suggestion)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Suggestion::STATUSES)],
        ]);

        $suggestion->status = $validated['status'];
        $suggestion->handled_by = $request->user()->id;
        $suggestion->handled_at = now();
        $suggestion->save();

        return response()->json([
            'success' => true,
            'data' => $suggestion->fresh(['user:id,username', 'handler:id,username']),
            'message' => 'Suggestion mise à jour',
        ]);
    }
}
