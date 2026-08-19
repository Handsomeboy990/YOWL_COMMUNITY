<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

class TagController extends Controller
{
    /**
     * How long the unfiltered tag list is served from cache.
     *
     * It is public, called on every page carrying the filter panel, and its
     * content changes only when somebody publishes a new tag.
     */
    private const CACHE_SECONDS = 600;

    public function index()
    {
        $search = request('search');
        $term = is_string($search) ? trim($search) : '';

        // Seule la liste complete est mise en cache : mettre en cache chaque
        // recherche reviendrait a stocker une entree par frappe au clavier.
        if ($term === '') {
            $tags = Cache::remember(
                'tags.all',
                self::CACHE_SECONDS,
                fn () => Tag::orderBy('name')->limit(50)->get()
            );
        } else {
            $tags = Tag::whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($term).'%'])
                ->orderBy('name')
                ->limit(50)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $tags,
        ], 200);
    }
}
