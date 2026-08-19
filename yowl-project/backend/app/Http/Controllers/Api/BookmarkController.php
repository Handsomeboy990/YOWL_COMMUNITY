<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Review;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * The reviews a member kept, most recently saved first.
     */
    public function index(Request $request)
    {
        $reviews = Review::query()
            ->whereIn('id', Bookmark::where('user_id', $request->user()->id)->select('review_id'))
            ->where(function ($query) use ($request) {
                // Un avis retire du fil reste consultable par son auteur.
                $query->where('is_published', true)
                    ->orWhere('user_id', $request->user()->id);
            })
            ->with(['user:id,username,picture', 'tags'])
            ->withCount('comments')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'message' => 'Bookmarks retrieved successfully.',
        ]);
    }

    public function store(Request $request, Review $review)
    {
        Bookmark::firstOrCreate([
            'user_id' => $request->user()->id,
            'review_id' => $review->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['bookmarked' => true],
            'message' => 'Avis enregistré',
        ]);
    }

    public function destroy(Request $request, Review $review)
    {
        Bookmark::where('user_id', $request->user()->id)
            ->where('review_id', $review->id)
            ->delete();

        return response()->json([
            'success' => true,
            'data' => ['bookmarked' => false],
            'message' => 'Avis retiré de tes enregistrements',
        ]);
    }

    /**
     * The identifiers only, so the feed can mark its cards in one call.
     */
    public function ids(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => Bookmark::where('user_id', $request->user()->id)->pluck('review_id'),
            'message' => 'Bookmark ids retrieved successfully.',
        ]);
    }
}
