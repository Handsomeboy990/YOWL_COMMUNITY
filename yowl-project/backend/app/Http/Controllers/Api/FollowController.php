<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowController extends Controller
{
    /**
     * What a member is allowed to follow.
     *
     * @var array<string, class-string>
     */
    private const TYPES = [
        'user' => User::class,
        'tag' => Tag::class,
    ];

    /**
     * Follow a member or a tag.
     *
     * Idempotent: following twice is the same as following once, so a double
     * tap on a slow connection does not produce an error the member cannot
     * understand.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'id' => 'required|integer|min:1',
        ]);

        $class = self::TYPES[$validated['type']];
        $target = $class::find($validated['id']);
        if (! $target) {
            return response()->json(['success' => false, 'message' => 'Introuvable.'], 404);
        }

        if ($target instanceof User && $target->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tu ne peux pas te suivre toi-même.',
            ], 422);
        }

        Follow::firstOrCreate([
            'user_id' => $request->user()->id,
            'followable_type' => $class,
            'followable_id' => $target->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['following' => true, 'followers' => $this->followerCount($class, $target->id)],
            'message' => 'Abonnement enregistré',
        ]);
    }

    /**
     * Stop following a member or a tag.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'id' => 'required|integer|min:1',
        ]);

        $class = self::TYPES[$validated['type']];

        Follow::where('user_id', $request->user()->id)
            ->where('followable_type', $class)
            ->where('followable_id', $validated['id'])
            ->delete();

        return response()->json([
            'success' => true,
            'data' => ['following' => false, 'followers' => $this->followerCount($class, $validated['id'])],
            'message' => 'Abonnement retiré',
        ]);
    }

    /**
     * Everything the current member follows, for the client to mark its lists.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $user->followedUsers()->get(['users.id', 'username', 'fullname', 'picture']),
                'tags' => $user->followedTags()->get(['tags.id', 'name']),
                'followers_count' => $user->followers()->count(),
            ],
            'message' => 'Follows retrieved successfully.',
        ]);
    }

    /**
     * Members worth following: the most followed, then the most active,
     * excluding the ones already followed and the member themselves.
     */
    public function suggestions(Request $request)
    {
        $user = $request->user();

        $already = Follow::where('user_id', $user->id)
            ->where('followable_type', User::class)
            ->pluck('followable_id')
            ->push($user->id);

        $suggestions = User::query()
            ->whereNull('anonymized_at')
            ->where('is_active', true)
            ->whereNotIn('id', $already)
            // whereHas plutot que having sur l'alias de withCount : un HAVING
            // sans GROUP BY ne voit pas cet alias sur tous les moteurs.
            ->whereHas('reviews')
            ->withCount(['reviews', 'followers'])
            ->orderByDesc('followers_count')
            ->orderByDesc('reviews_count')
            ->limit(6)
            ->get(['id', 'username', 'fullname', 'picture']);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
            'message' => 'Suggestions retrieved successfully.',
        ]);
    }

    /**
     * Members matching a handle fragment, for the mention autocomplete.
     *
     * Anybody who blocked the searcher is left out: a mention is otherwise a
     * way to reach somebody who does not want to be reached.
     */
    public function searchMembers(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:30',
        ]);

        $terme = mb_strtolower($validated['q']);

        $blockedBy = \App\Models\Block::where('blocked_id', $request->user()->id)->pluck('user_id');
        $blocked = \App\Models\Block::where('user_id', $request->user()->id)->pluck('blocked_id');

        $membres = User::query()
            ->whereRaw('LOWER(username) LIKE ?', [$terme.'%'])
            ->whereNull('anonymized_at')
            ->where('is_active', true)
            ->whereNotIn('id', $blockedBy)
            ->whereNotIn('id', $blocked)
            ->orderBy('username')
            ->limit(6)
            ->get(['id', 'username', 'fullname', 'picture']);

        return response()->json([
            'success' => true,
            'data' => $membres,
            'message' => 'Members retrieved successfully.',
        ]);
    }

    private function followerCount(string $class, int $id): int
    {
        return Follow::where('followable_type', $class)->where('followable_id', $id)->count();
    }
}
