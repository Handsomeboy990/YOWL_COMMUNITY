<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\Cached;

class TagController extends Controller
{
    public function index()
    {
        $search = request('search');
        $term = is_string($search) ? trim($search) : '';

        // Seule la liste complete est mise en cache : mettre en cache chaque
        // recherche reviendrait a stocker une entree par frappe au clavier.
        if ($term === '') {
            $tags = Cached::remember(
                Cached::TAGS,
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
