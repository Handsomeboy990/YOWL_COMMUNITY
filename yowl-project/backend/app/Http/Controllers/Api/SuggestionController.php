<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    /**
     * Record a suggestion sent from the public suggestion form.
     *
     * The route is open so a visitor can write without an account; the author
     * is attached whenever the request carries a valid token.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email:rfc|max:255',
            'message' => 'required|string|min:5|max:2000',
        ]);

        $validated['user_id'] = auth('sanctum')->id();

        $suggestion = Suggestion::create($validated);

        return response()->json([
            'success' => true,
            'data' => $suggestion,
            'message' => 'Merci, ta suggestion a bien été enregistrée.',
        ], 201);
    }
}
