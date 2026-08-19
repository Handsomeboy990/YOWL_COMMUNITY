<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Follow;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class TagFeedController extends Controller
{
    /**
     * A tag as a place rather than a filter value.
     *
     * A tag was only ever a query parameter, so there was nothing to link to,
     * nothing to follow from, and no way to tell an active subject from a dead
     * one. This gives each one an address, a headcount and its own feed.
     */
    public function show(Request $request, string $name)
    {
        $tag = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if (! $tag) {
            return response()->json(['success' => false, 'message' => 'Sujet introuvable.'], 404);
        }

        $viewer = auth('sanctum')->user();

        $reviewIds = $tag->reviews()->where('is_published', true)->select('reviews.id');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'stats' => [
                    'reviews' => (clone $reviewIds)->count(),
                    'followers' => $tag->followers()->count(),
                    'contributors' => Review::whereIn('id', $reviewIds)->distinct()->count('user_id'),
                    'this_week' => Review::whereIn('id', $reviewIds)
                        ->where('created_at', '>=', now()->subWeek())->count(),
                ],
                'following' => $viewer
                    ? Follow::where('user_id', $viewer->id)
                        ->where('followable_type', Tag::class)
                        ->where('followable_id', $tag->id)->exists()
                    : false,
                'top_contributors' => $this->topContributors($tag),
                'related' => $this->relatedTags($tag),
            ],
            'message' => 'Tag retrieved successfully.',
        ]);
    }

    /**
     * The reviews carrying this tag, ranked or chronological.
     */
    public function reviews(Request $request, string $name)
    {
        $tag = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->firstOrFail();
        $viewer = auth('sanctum')->user();

        $query = $tag->reviews()
            ->where('is_published', true)
            ->with(['user:id,username,picture', 'tags', 'poll.options'])
            ->withCount('comments');

        if ($viewer) {
            $query->whereNotIn('reviews.user_id', Block::where('user_id', $viewer->id)->select('blocked_id'));
        }

        $query = match (request('sort')) {
            'relevant' => $query->orderByDesc('score')->orderByDesc('reviews.created_at'),
            'older' => $query->orderBy('reviews.created_at'),
            default => $query->orderByDesc('reviews.created_at'),
        };

        return response()->json([
            'success' => true,
            'data' => $query->paginate(10),
            'message' => 'Reviews retrieved successfully.',
        ]);
    }

    /**
     * The most followed tags, for the directory.
     */
    public function index()
    {
        $tags = Tag::query()
            // whereHas et non having sur l'alias de withCount : un HAVING sans
            // GROUP BY ne voit pas cet alias sur tous les moteurs.
            ->whereHas('reviews', fn ($q) => $q->where('is_published', true))
            ->withCount(['reviews', 'followers'])
            ->orderByDesc('reviews_count')
            ->limit(60)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $tags,
            'message' => 'Tags retrieved successfully.',
        ]);
    }

    /**
     * Who writes here most, so a newcomer knows who to follow.
     */
    private function topContributors(Tag $tag)
    {
        return User::query()
            ->whereNull('anonymized_at')
            ->whereIn('id', $tag->reviews()->where('is_published', true)->select('reviews.user_id'))
            ->withCount(['reviews' => fn ($q) => $q->whereIn('reviews.id', $tag->reviews()->select('reviews.id'))])
            ->orderByDesc('reviews_count')
            ->limit(4)
            ->get(['id', 'username', 'fullname', 'picture']);
    }

    /**
     * Tags that appear alongside this one, which is how somebody discovers
     * the next subject without going back to a list.
     */
    private function relatedTags(Tag $tag)
    {
        return Tag::query()
            ->where('tags.id', '!=', $tag->id)
            ->whereHas('reviews', fn ($q) => $q->whereIn('reviews.id', $tag->reviews()->select('reviews.id')))
            ->withCount('reviews')
            ->orderByDesc('reviews_count')
            ->limit(6)
            ->get(['id', 'name']);
    }
}
