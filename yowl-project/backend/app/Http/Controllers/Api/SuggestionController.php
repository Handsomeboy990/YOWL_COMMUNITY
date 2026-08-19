<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        if (! Settings::get('suggestions.open')) {
            return response()->json([
                'success' => false,
                'message' => 'Le formulaire de suggestion est momentanément fermé.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email:rfc|max:255',
            'subject' => ['nullable', Rule::in(Suggestion::SUBJECTS)],
            'message' => 'required|string|min:5|max:2000',
        ], [
            'message.min' => 'Ta suggestion doit faire au moins 5 caractères.',
            'message.max' => 'Ta suggestion ne peut pas dépasser 2000 caractères.',
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
