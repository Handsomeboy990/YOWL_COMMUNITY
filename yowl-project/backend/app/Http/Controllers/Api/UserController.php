<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Support\Media;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use App\Mail\EmailVerificationCode;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        // Le propriétaire voit son profil complet, les autres un profil public
        if ($request->user() && $request->user()->id === $user->id) {
            $user['roles'] = $user->getRoleNames();

            return $user;
        }

        return response()->json(
            $user->only(['id', 'username', 'fullname', 'picture', 'created_at'])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Vérifier que l'utilisateur modifie bien son propre profil
        if ($user->id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'username' => ['string', 'max:255', 'min:3', 'unique:users,username,' . $user->id],
                'fullname' => ['string', 'max:255', 'min:5'],
                'email' => ['string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'picture' => ['nullable', 'file', 'image', 'max:2048'],
            ]);

            if ($request->hasFile('picture')) {
                $previous = $user->picture;
                $validated['picture'] = Media::store($request->file('picture'), 'profile');
                if ($previous) {
                    Media::delete($previous);
                }
            }

            // Changer d'adresse annule la verification : sans cela, on
            // s'attribuait une adresse qu'on ne controle pas, deja marquee
            // comme verifiee, et les emails de service partaient dessus.
            $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;

            $user->update($validated);

            if ($emailChanged) {
                // email_verified_at n'est pas assignable en masse : passer par
                // update() l'aurait ignore en silence, et la nouvelle adresse
                // serait restee marquee comme verifiee.
                $code = (string) random_int(100000, 999999);
                $user->forceFill([
                    'email_verified_at' => null,
                    'email_verification_code' => $code,
                    'email_verification_expires_at' => now()->addMinutes(15),
                ])->save();

                Mail::to($user->email)->send(new EmailVerificationCode($code));
            }

            $user['roles'] = $user->getRoleNames();

            return response()->json([
                'success' => true,
                'data' => $user,
                'email_verification_required' => $emailChanged,
                'message' => $emailChanged
                    ? 'Profil mis à jour. Un code de vérification a été envoyé à ta nouvelle adresse.'
                    : 'Profil mis à jour avec succès',
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->errors(),
                ],
                422,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        // Vérifier que l'utilisateur supprime bien son propre compte
        if ($user->id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }

        // La suppression se faisait par un simple is_active a false : l'adresse,
        // le pseudo, le nom, la photo et la date de naissance restaient en base
        // indefiniment, alors que le produit annonce une suppression.
        //
        // Les donnees personnelles partent, les contributions restent
        // rattachees a un compte anonyme pour ne pas trouer les discussions
        // des autres membres.
        $avatar = $user->picture;

        $user->forceFill([
            'username' => 'membre-supprime-'.$user->id,
            'fullname' => 'Membre supprimé',
            'email' => 'supprime-'.$user->id.'@yowl.invalid',
            'picture' => null,
            'birthdate' => null,
            'password' => Hash::make(Str::random(40)),
            'email_verified_at' => null,
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'remember_token' => null,
            'is_active' => false,
            'anonymized_at' => now(),
        ])->save();

        if ($avatar) {
            Media::delete($avatar);
        }

        $user->tokens()->delete();
        $user->notifications()->delete();
        $user->pushSubscriptions()->delete();
        $user->syncRoles([]);

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé. Tes données personnelles ont été effacées.',
        ]);
    }

    /**
     * The public profile of a member, reached by their handle.
     *
     * Mentions point here, so it has to work without knowing an identifier
     * and without being signed in.
     */
    public function publicProfile(Request $request, string $username)
    {
        $user = User::where('username', $username)->whereNull('anonymized_at')->first();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Membre introuvable.'], 404);
        }

        $viewer = auth('sanctum')->user();

        $totals = $user->reviews()->where('is_published', true)
            ->selectRaw('COUNT(*) AS reviews')
            ->selectRaw('COALESCE(SUM(nb_like), 0) AS likes')
            ->selectRaw('COALESCE(SUM(nb_views), 0) AS views')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'picture' => $user->picture,
                'created_at' => $user->created_at,
                'is_active' => $user->is_active,
                'stats' => [
                    'reviews' => (int) $totals->reviews,
                    'likes' => (int) $totals->likes,
                    'views' => (int) $totals->views,
                    'followers' => $user->followers()->count(),
                    'following' => $user->followedUsers()->count(),
                ],
                'following' => $viewer
                    ? \App\Models\Follow::where('user_id', $viewer->id)
                        ->where('followable_type', User::class)
                        ->where('followable_id', $user->id)->exists()
                    : false,
                'blocked' => $viewer
                    ? \App\Models\Block::where('user_id', $viewer->id)
                        ->where('blocked_id', $user->id)->exists()
                    : false,
                'is_me' => $viewer?->id === $user->id,
            ],
            'message' => 'Profile retrieved successfully.',
        ]);
    }

    /**
     * The published reviews of a member, for their public profile.
     */
    public function publicReviews(string $username)
    {
        $user = User::where('username', $username)->whereNull('anonymized_at')->firstOrFail();

        $reviews = $user->reviews()
            ->where('is_published', true)
            ->with(['user:id,username,picture', 'tags'])
            ->withCount('comments')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'message' => 'Reviews retrieved successfully.',
        ]);
    }

    /**
     * The reviews written by a member, newest first.
     *
     * The profile used to build this list by filtering the global feed held in
     * the client store, which only ever holds one page of ten. A member with
     * more than a handful of reviews simply never saw most of them.
     */
    public function reviews(Request $request, User $user)
    {
        $isOwner = $request->user()->id === $user->id;

        $reviews = $user->reviews()
            ->when(! $isOwner, fn ($query) => $query->where('is_published', true))
            ->with(['tags'])
            ->withCount('comments')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'message' => 'Reviews retrieved successfully.',
        ]);
    }

    /**
     * Aggregate statistics of a member, computed in the database.
     *
     * These figures used to be summed in the browser over the same partial
     * feed page, so the counters shown on the profile were wrong for anyone
     * with more than a page of activity.
     */
    public function stats(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }

        $totals = $user->reviews()
            ->selectRaw('COUNT(*) AS reviews')
            ->selectRaw('COALESCE(SUM(nb_views), 0) AS views')
            ->selectRaw('COALESCE(SUM(nb_like), 0) AS likes')
            ->selectRaw('COALESCE(SUM(nb_dislike), 0) AS dislikes')
            ->first();

        $commentsReceived = Comment::whereIn('review_id', $user->reviews()->select('id'))
            ->where('user_id', '!=', $user->id)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => (int) $totals->reviews,
                'views' => (int) $totals->views,
                'likes' => (int) $totals->likes,
                'dislikes' => (int) $totals->dislikes,
                'comments_written' => $user->comments()->count(),
                'comments_received' => $commentsReceived,
                'reviews_per_month' => $this->reviewsPerMonth($user),
            ],
            'message' => 'Stats retrieved successfully.',
        ]);
    }

    /**
     * Reviews published each month over the last six months, oldest first.
     *
     * Counted month by month with date boundaries rather than with a grouped
     * date expression, which keeps it portable and fills the empty months
     * that a GROUP BY would silently omit.
     *
     * @return array<int, array{month: string, count: int}>
     */
    private function reviewsPerMonth(User $user): array
    {
        $series = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $start = now()->startOfMonth()->subMonths($offset);
            $end = $start->copy()->addMonth();

            $series[] = [
                'month' => $start->format('Y-m'),
                'count' => $user->reviews()
                    ->where('created_at', '>=', $start)
                    ->where('created_at', '<', $end)
                    ->count(),
            ];
        }

        return $series;
    }

    /**
     * Get user activity (reviews, comments)
     */
    public function activity(Request $request, User $user)
    {
        // L'activité est privée : seul le propriétaire peut la consulter
        if ($request->user()->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }

        $activities = [];
        // Reviews
        foreach ($user->reviews()->latest()->limit(10)->get() as $review) {
            $activities[] = [
                'text' => "Vous avez publié une review : " . (mb_strimwidth($review->content, 0, 40, '...')),
                'type' => 'review',
                'created_at' => $review->created_at,
            ];
        }
        // Comments
        foreach ($user->comments()->latest()->limit(10)->get() as $comment) {
            $activities[] = [
                'text' => "Vous avez commenté : " . (mb_strimwidth($comment->content, 0, 40, '...')),
                'type' => 'commentaire',
                'created_at' => $comment->created_at,
            ];
        }
        // Réactions sur les reviews
        foreach ($user->reviewReactions()->latest()->limit(10)->get() as $reaction) {
            $review = $reaction->review;
            $type = $reaction->reaction === 'like' ? 'aimé' : 'pas aimé';
            $activities[] = [
                'text' => "Vous avez $type une review : " . (mb_strimwidth($review ? $review->content : '', 0, 40, '...')),
                'type' => 'réaction',
                'created_at' => $reaction->created_at,
            ];
        }
        // Tri par date décroissante
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        return response()->json($activities);
    }
}
