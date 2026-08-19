<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Report;
use App\Models\Review;
use App\Models\Comment;
use App\Models\Suggestion;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Support\Cached;
use App\Support\Media;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

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
            'data' => Cached::remember(Cached::ADMIN_STATS, fn () => [
                'users' => User::whereNull('anonymized_at')->count(),
                'reviews' => Review::count(),
                'comments' => Comment::count(),
                'tags' => Tag::count(),
                'pending_reports' => Report::where('status', Report::STATUS_PENDING)->count(),
                'new_suggestions' => Suggestion::where('status', Suggestion::STATUS_NEW)->count(),
                'latest_reviews' => Review::latest()->take(5)->get(['id','content','created_at']),
            ]),
        ]);
    }

    /**
     * Create a member from the console.
     *
     * The account is created already verified: an administrator adding a
     * colleague should not have to wait for a code sent to an address they do
     * not own. The password is typed by the administrator and handed over out
     * of band; it is never returned by this endpoint.
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username'],
            'fullname' => ['required', 'string', 'min:5', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'roles' => ['present', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'fullname' => $validated['fullname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'birthdate' => $validated['birthdate'] ?? null,
        ]);

        // email_verified_at n'est pas assignable en masse, volontairement :
        // le champ ne doit jamais pouvoir venir d'une requete. Il est pose ici
        // explicitement, l'administrateur repondant de l'adresse qu'il saisit.
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->syncRoles($validated['roles']);

        AuditLog::record('user.created', $user, ['roles' => $validated['roles']], $request);

        $user['roles'] = $user->getRoleNames();

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Membre créé',
        ], 201);
    }

    /**
     * The full record of one member, for the administration detail panel.
     *
     * Everything an administrator needs to judge an account without opening
     * five screens: who they are, what they published, what they received,
     * and what has been reported about them.
     */
    public function showUser(User $user)
    {
        $totals = $user->reviews()
            ->selectRaw('COUNT(*) AS reviews')
            ->selectRaw('COALESCE(SUM(nb_views), 0) AS views')
            ->selectRaw('COALESCE(SUM(nb_like), 0) AS likes')
            ->selectRaw('COALESCE(SUM(nb_dislike), 0) AS dislikes')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'picture' => $user->picture,
                    'birthdate' => $user->birthdate?->format('Y-m-d'),
                    'is_active' => $user->is_active,
                    'anonymized_at' => $user->anonymized_at,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'roles' => $user->getRoleNames(),
                ],
                'stats' => [
                    'reviews' => (int) $totals->reviews,
                    'views' => (int) $totals->views,
                    'likes_received' => (int) $totals->likes,
                    'dislikes_received' => (int) $totals->dislikes,
                    'comments_written' => $user->comments()->count(),
                    'reactions_given' => $user->reviewReactions()->count(),
                    'reports_filed' => Report::where('user_id', $user->id)->count(),
                    'reports_received' => $this->reportsAgainst($user),
                ],
                'recent_reviews' => $user->reviews()
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'content', 'nb_like', 'nb_views', 'is_published', 'created_at']),
                'recent_comments' => $user->comments()
                    ->with('review:id')
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'review_id', 'content', 'nb_like', 'created_at']),
            ],
            'message' => 'Member retrieved successfully.',
        ]);
    }

    /**
     * How many reports target content written by this member.
     */
    private function reportsAgainst(User $user): int
    {
        $reviewIds = $user->reviews()->pluck('id');
        $commentIds = $user->comments()->pluck('id');

        return Report::where(function ($query) use ($reviewIds, $commentIds) {
            $query->where(function ($sub) use ($reviewIds) {
                $sub->where('reportable_type', Review::class)->whereIn('reportable_id', $reviewIds);
            })->orWhere(function ($sub) use ($commentIds) {
                $sub->where('reportable_type', Comment::class)->whereIn('reportable_id', $commentIds);
            });
        })->count();
    }

    /**
     * Edit the identity of a member from the console.
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'min:3', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'fullname' => ['sometimes', 'string', 'min:5', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'birthdate' => ['sometimes', 'nullable', 'date', 'before:today'],
        ]);

        $before = $user->only(array_keys($validated));
        $user->update($validated);

        AuditLog::record('user.updated', $user, ['from' => $before, 'to' => $validated], $request);

        $user['roles'] = $user->getRoleNames();

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Membre mis à jour',
        ]);
    }

    /**
     * Issue a new password for a member and return it once.
     *
     * The administrator hands it over out of band. Every existing token is
     * revoked in the same move: a password reset that leaves the old sessions
     * alive protects nobody.
     */
    public function regeneratePassword(Request $request, User $user)
    {
        if ($user->anonymized_at) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte a été supprimé.',
            ], 422);
        }

        $password = Str::password(16);
        $user->forceFill(['password' => Hash::make($password)])->save();
        $user->tokens()->delete();

        AuditLog::record('user.password_regenerated', $user, [], $request);

        return response()->json([
            'success' => true,
            // Affiche une seule fois : il n'est stocke nulle part en clair.
            'data' => ['password' => $password],
            'message' => 'Mot de passe régénéré. Il ne sera plus affiché.',
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
            'subject' => ['nullable', Rule::in(Suggestion::SUBJECTS)],
        ]);

        $query = Suggestion::with(['user:id,username,picture', 'handler:id,username']);
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['subject'])) {
            $query->where('subject', $validated['subject']);
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
