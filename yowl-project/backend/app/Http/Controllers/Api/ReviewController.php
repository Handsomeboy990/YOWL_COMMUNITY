<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $query = Review::with(['user', 'tags', 'comments']);

            // Recherche
            $search = request('search');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('content', 'like', "%$search%")
                        ->orWhere('link', 'like', "%$search%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('username', 'like', "%$search%");
                        })
                         ->orWhereHas('tags', function ($tagQuery) use ($search) {
                            $tagQuery->where('name', 'like', "%$search%");
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

            $reviews = $query->orderByDesc('created_at')->paginate(10);

            return response()->json(
                [
                    'success' => true,
                    'data' => $reviews,
                    'message' => 'Reviews retrieved successfully.',
                ],
                200,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'message' => 'Unable to retrieve reviews. Please try again later.',
                ],
                502,
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
                'tags' => 'nullable|array',
                'content' => 'required|string',
                'link' => 'nullable|string',
                'medias' => 'nullable',
                'medias.*' => 'file|image',
            ]);
            $validated['user_id'] = $request->user()->id;

            $mediaPaths = [];
            if ($request->hasFile('medias')) {
                foreach ($request->file('medias') as $media) {
                    $mediaPaths[] = $media->store('reviews', 'public');
                }
            }
            $validated['medias'] = json_encode($mediaPaths);

            $review = Review::create($validated);

            // Lier les tags à la review créée
            $tags = $request->input('tags');
            if ($tags) {
                // if (!is_array($tags)) {
                //     $decoded = json_decode($tagsInput, true);
                //     if (is_array($decoded)) $tagsArray = $decoded;
                //     else $tagsArray = array_filter(array_map('trim', explode(',', $tagsInput)));
                // } else {
                //     $tagsArray = $tagsInput;
                // }
                $tagIds = [];
                foreach ($tags as $tag) {
                    if (!strlen(trim($tag))) continue;
                    $createdTag = Tag::firstOrCreate(['name' => $tag]);
                    $tagIds[] = $createdTag->id;
                }
                if (count($tagIds)) {
                    $review->tags()->sync($tagIds);
                }
            }

            return response()->json(
                [
                    'success' => true,
                    'data' => $review->load(['user', 'tags']),
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
            $review->load(['user', 'tags', 'comments']);
            $review->nb_views++;
            $review->save();
            return response()->json(
                [
                    'success' => true,
                    'data' => $review,
                    'message' => 'Review retrieved successfully.',
                ],
                200,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'message' => 'Unable to retrieve review. Please try again later.',
                ],
                502,
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
                'content' => 'sometimes|required|string',
                'link' => 'nullable|string',
                'tags' => 'nullable|array',
                'medias.*' => 'nullable|file|image',
                'existingMedias' => 'nullable|array',
            ]);

            // Récupérer les anciennes images à garder
            $existingMedias = $request->input('existingMedias', []);
            if (!is_array($existingMedias)) {
                $existingMedias = json_decode($existingMedias, true) ?? [];
            }

            // Récupérer les anciennes images
            $oldMedias = $review->medias ? json_decode($review->medias, true) : [];

            // Supprimer physiquement les images retirées
            $toDelete = array_diff($oldMedias, $existingMedias);
            foreach ($toDelete as $mediaPath) {
                Storage::disk('public')->delete($mediaPath);
            }

            // Ajouter les nouvelles images
            $newMediaPaths = [];
            if ($request->hasFile('medias')) {
                foreach ($request->file('medias') as $media) {
                    $newMediaPaths[] = $media->store('reviews', 'public');
                }
            }

            // Fusionner les anciennes à garder et les nouvelles
            $allMedias = array_merge($existingMedias, $newMediaPaths);
            $validated['medias'] = json_encode($allMedias);
            unset($validated['existingMedias']);

            // Modifier les tags
            $tags = $request->input('tags');
            if ($tags !== null) {
                // if (!is_array($tags)) {
                //     $decoded = json_decode($tags, true);
                //     if (is_array($decoded)) $tagsArray = $decoded;
                //     else $tagsArray = array_filter(array_map('trim', explode(',', $tags)));
                // } else {
                //     $tagsArray = $tagsInput;
                // }
                $tagIds = [];
                foreach ($tags as $tag) {
                    if (!strlen(trim($tag))) continue;
                    $createdTag = Tag::firstOrCreate(['name' => $tag]);
                    $tagIds[] = $createdTag->id;
                }
                $review->tags()->sync($tagIds);
            } else {
                $review->tags()->sync([]);
            }

            $review->update($validated);

            return response()->json(
                [
                    'success' => true,
                    'data' => $review->load(['user', 'tags']),
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
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'message' => 'Unable to update review. Please try again later.',
                ],
                502,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Review $review)
    {
        //
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Action non autorisée'], 403);
        }
        try {
            $review->delete();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Review deleted successfully.',
                ],
                200,
            );
        } catch (\Exception $error) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $error->getMessage(),
                    'message' => 'Unable to delete review. Please try again later.',
                ],
                502,
            );
        }
    }
}
