<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\FetchLinkPreview;
use App\Models\Review;
use App\Models\ReviewReaction;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Support\Media;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Upload rules shared by store and update.
     *
     * Images are restricted by extension and by size so a request cannot push
     * an arbitrary payload, or an unbounded one, into the storage disk.
     */
    private const MAX_MEDIAS = 5;

    private const MEDIA_RULES = 'file|mimes:jpeg,jpg,png,webp,gif|max:5120';

    private const CONTENT_MAX = 5000;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $currentUser = auth('sanctum')->user();

            $query = Review::with(['user:id,username,picture', 'tags', 'comments']);

            // Ne montrer que les reviews publiées (sauf celles de l'utilisateur connecté)
            $query->where(function ($q) use ($currentUser) {
                $q->where('is_published', true);
                if ($currentUser) {
                    $q->orWhere('user_id', $currentUser->id);
                }
            });

            // Recherche (insensible à la casse, portable MySQL/PostgreSQL/SQLite)
            $search = request('search');
            if ($search) {
                $needle = '%'.mb_strtolower($search).'%';
                $query->where(function ($q) use ($needle) {
                    $q->whereRaw('LOWER(content) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(link) LIKE ?', [$needle])
                        ->orWhereHas('user', function ($userQuery) use ($needle) {
                            $userQuery->whereRaw('LOWER(username) LIKE ?', [$needle]);
                        })
                        ->orWhereHas('tags', function ($tagQuery) use ($needle) {
                            $tagQuery->whereRaw('LOWER(name) LIKE ?', [$needle]);
                        });
                });
            }

            // Filtre : reviews sans réponses
            if (request('noAnswers')) {
                $query->whereDoesntHave('comments');
            }
            // Filtre : reviews sans vues
            if (request('noViews')) {
                $query->where(function ($q) {
                    $q->whereNull('nb_views')->orWhere('nb_views', 0);
                });
            }
            // Filtre : reviews sans likes
            if (request('noLikes')) {
                $query->where(function ($q) {
                    $q->whereNull('nb_like')->orWhere('nb_like', 0);
                });
            }
            // Fil personnalise : uniquement les membres et les tags suivis.
            // Sans le graphe, tout le monde voyait exactement la meme chose.
            if (request('feed') === 'following' && $currentUser) {
                $followedUsers = \App\Models\Follow::where('user_id', $currentUser->id)
                    ->where('followable_type', \App\Models\User::class)
                    ->pluck('followable_id');
                $followedTags = \App\Models\Follow::where('user_id', $currentUser->id)
                    ->where('followable_type', \App\Models\Tag::class)
                    ->pluck('followable_id');

                $query->where(function ($q) use ($followedUsers, $followedTags) {
                    $q->whereIn('user_id', $followedUsers);
                    if ($followedTags->isNotEmpty()) {
                        $q->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $followedTags));
                    }
                });
            }

            // Filtre : tags (accept comma separated names or single name)
            if (request('tags')) {
                $tags = request('tags');
                if (!is_array($tags)) {
                    $tags = array_filter(array_map('trim', explode(',', $tags)));
                }
                if (count($tags)) {
                    $query->whereHas('tags', function ($q) use ($tags) {
                        $q->whereIn('name', $tags);
                    });
                }
            }

            // Tri
            $sort = request('sort');
            if ($sort === 'newest') {
                $query->orderByDesc('created_at');
            } elseif ($sort === 'older') {
                $query->orderBy('created_at', 'asc');
            } elseif ($sort === 'highestLike') {
                $query->orderByDesc('nb_like');
            } else {
                $query->orderByDesc('created_at'); // défaut
            }

            $reviews = $query->paginate(10);

            // Réaction de l'utilisateur connecté sur chaque review de la page
            $userReactions = collect();
            if ($currentUser) {
                $userReactions = ReviewReaction::where('user_id', $currentUser->id)
                    ->whereIn('review_id', $reviews->getCollection()->pluck('id'))
                    ->pluck('reaction', 'review_id');
            }
            $followedAuthors = collect();
            if ($currentUser) {
                $followedAuthors = \App\Models\Follow::where('user_id', $currentUser->id)
                    ->where('followable_type', \App\Models\User::class)
                    ->whereIn('followable_id', $reviews->getCollection()->pluck('user_id'))
                    ->pluck('followable_id')
                    ->flip();
            }

            $reviews->getCollection()->transform(function ($review) use ($userReactions, $followedAuthors) {
                $review->user_reaction = $userReactions[$review->id] ?? null;
                $review->author_followed = $followedAuthors->has($review->user_id);

                return $review;
            });

            return response()->json(
                [
                    'success' => true,
                    'data' => $reviews,
                    'message' => 'Reviews retrieved successfully.',
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error('Review listing failed: '.$e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unable to retrieve reviews. Please try again later.',
                ],
                500,
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tags' => 'nullable|array|max:10',
                'tags.*' => 'string|max:30',
                'content' => 'required|string|max:'.self::CONTENT_MAX,
                'link' => 'nullable|url:http,https|max:2048',
                'medias' => 'nullable|array|max:'.self::MAX_MEDIAS,
                'medias.*' => self::MEDIA_RULES,
            ]);
            $validated['user_id'] = $request->user()->id;

            $mediaPaths = [];
            if ($request->hasFile('medias')) {
                foreach ($request->file('medias') as $media) {
                    $mediaPaths[] = Media::store($media, 'reviews');
                }
            }
            // Le cast "array" du modèle se charge de l'encodage JSON
            $validated['medias'] = $mediaPaths;

            $review = Review::create($validated);

            if ($review->link) {
                FetchLinkPreview::dispatch($review->id);
            }

            // Lier les tags à la review créée
            $tags = $request->input('tags');
            if ($tags) {
                $tagIds = [];
                foreach ($tags as $tag) {
                    if (!strlen(trim($tag))) continue;
                    $tagName = trim($tag);
                    $createdTag = Tag::firstOrCreate(['name' => strtolower($tagName)]);
                    $tagIds[] = $createdTag->id;
                }
                if (count($tagIds)) {
                    $review->tags()->sync($tagIds);
                }
            }

            return response()->json(
                [
                    'success' => true,
                    'data' => $review->load(['user:id,username,picture', 'tags']),
                    'message' => 'Review created successfully.',
                ],
                201,
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->errors(),
                    'message' => 'Review creation failed. Please check your input.',
                ],
                422,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Review $review)
    {

        try {
            $currentUser = auth('sanctum')->user();

            // Une review dépubliée n'est visible que par son auteur
            if (! $review->is_published && (! $currentUser || $currentUser->id !== $review->user_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review introuvable.',
                ], 404);
            }

            $review->load(['user:id,username,picture', 'tags', 'comments']);
            // Incrément atomique : deux lectures simultanées ne s'écrasent pas
            $review->increment('nb_views');

            $review->user_reaction = $currentUser
                ? ReviewReaction::where('user_id', $currentUser->id)
                    ->where('review_id', $review->id)
                    ->value('reaction')
                : null;

            return response()->json(
                [
                    'success' => true,
                    'data' => $review,
                    'message' => 'Review retrieved successfully.',
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error('Review retrieval failed: '.$e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unable to retrieve review. Please try again later.',
                ],
                500,
            );
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Review $review)
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Action non autorisée'], 403);
        }
        try {
            $validated = $request->validate([
                'content' => 'sometimes|required|string|max:'.self::CONTENT_MAX,
                'link' => 'nullable|url:http,https|max:2048',
                'tags' => 'nullable|array|max:10',
                'tags.*' => 'string|max:30',
                'medias' => 'nullable|array|max:'.self::MAX_MEDIAS,
                'medias.*' => self::MEDIA_RULES,
                'existingMedias' => 'nullable|array',
                'existingMedias.*' => 'string',
            ]);

            // Récupérer les anciennes images à garder
            $existingMedias = $request->input('existingMedias', []);
            if (!is_array($existingMedias)) {
                $existingMedias = json_decode($existingMedias, true) ?? [];
            }

            // Récupérer les anciennes images (le cast "array" décode déjà le JSON)
            $oldMedias = is_array($review->medias) ? $review->medias : [];

            // Ne garder que des chemins appartenant réellement à cette review :
            // un chemin arbitraire envoyé par le client est ignoré.
            $existingMedias = array_values(array_intersect($existingMedias, $oldMedias));

            // Supprimer physiquement les images retirées
            $toDelete = array_diff($oldMedias, $existingMedias);
            foreach ($toDelete as $mediaPath) {
                Media::delete($mediaPath);
            }

            // Ajouter les nouvelles images
            $newMediaPaths = [];
            if ($request->hasFile('medias')) {
                foreach ($request->file('medias') as $media) {
                    $newMediaPaths[] = Media::store($media, 'reviews');
                }
            }

            // Fusionner les anciennes à garder et les nouvelles
            $merged = array_values(array_merge($existingMedias, $newMediaPaths));
            if (count($merged) > self::MAX_MEDIAS) {
                foreach ($newMediaPaths as $justUploaded) {
                    Media::delete($justUploaded);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Une review ne peut pas dépasser '.self::MAX_MEDIAS.' images.',
                ], 422);
            }
            $validated['medias'] = $merged;
            unset($validated['existingMedias']);

            // Modifier les tags
            $tags = $request->input('tags');
            if ($tags !== null) {
                $tagIds = [];
                foreach ($tags as $tag) {
                    if (!strlen(trim($tag))) continue;
                    $tagName = trim($tag);
                    $createdTag = Tag::firstOrCreate(['name' => strtolower($tagName)]);
                    $tagIds[] = $createdTag->id;
                }
                $review->tags()->sync($tagIds);
            } else {
                $review->tags()->sync([]);
            }

            $linkBefore = $review->link;
            $review->update($validated);

            // Le lien a change : l'ancien apercu ne decrit plus rien.
            if ($review->link !== $linkBefore) {
                $review->forceFill(['link_preview' => null, 'link_preview_at' => null])->saveQuietly();
                if ($review->link) {
                    FetchLinkPreview::dispatch($review->id);
                }
            }

            return response()->json(
                [
                    'success' => true,
                    'data' => $review->load(['user:id,username,picture', 'tags']),
                    'message' => 'Review updated successfully.',
                ],
                200,
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->errors(),
                    'message' => 'Review update failed. Please check your input.',
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Review update failed: '.$e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unable to update review. Please try again later.',
                ],
                500,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Review $review)
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Action non autorisée'], 403);
        }
        try {
            // Supprimer physiquement les médias associés
            if (is_array($review->medias)) {
                foreach ($review->medias as $mediaPath) {
                    Media::delete($mediaPath);
                }
            }

            $review->delete();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Review deleted successfully.',
                ],
                200,
            );
        } catch (\Exception $error) {
            Log::error('Review deletion failed: '.$error->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unable to delete review. Please try again later.',
                ],
                500,
            );
        }
    }
}
