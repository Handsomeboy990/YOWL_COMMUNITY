<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = request('search');
        $query = Tag::query();
        if ($search && is_string($search) && strlen(trim($search))) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower(trim($search)).'%']);
        }
        $tags = $query->orderBy('name')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ], 200);
    }
}
