<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        // Le propriétaire voit son profil complet, les autres un profil public
        if ($request->user() && $request->user()->id === $user->id) {
            $user['roles'] = $user->getRoleNames();

            return $user;
        }

        return response()->json(
            $user->only(['id', 'username', 'fullname', 'picture', 'created_at'])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Vérifier que l'utilisateur modifie bien son propre profil
        if ($user->id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'username' => ['string', 'max:255', 'min:3', 'unique:users,username,' . $user->id],
                'fullname' => ['string', 'max:255', 'min:5'],
                'email' => ['string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'picture' => ['nullable', 'file', 'image', 'max:2048'],
                'password' => ['nullable', Rules\Password::defaults()],
            ]);

            if ($request->hasFile('picture')) {
                $path = $request->file('picture')->store('profile', 'public');
                $validated['picture'] = $path;
            }

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($request->string('password'));
            }
           $user->update($validated);
           $user["roles"] = $user->getRoleNames();
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Profil mis à jour avec succès',
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->errors(),
                ],
                422,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        // Vérifier que l'utilisateur supprime bien son propre compte
        if ($user->id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }

        $user->is_active = false;
        $user->save();

        // Révoquer tous les tokens du compte (toutes sessions confondues)
        $user->tokens()->delete();

        return response()->json(['success'=>true,'message'=>'Compte désactivé avec succès']);
    }

    /**
     * Get user activity (reviews, comments)
     */
    public function activity(Request $request, User $user)
    {
        // L'activité est privée : seul le propriétaire peut la consulter
        if ($request->user()->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        }

        $activities = [];
        // Reviews
        foreach ($user->reviews()->latest()->limit(10)->get() as $review) {
            $activities[] = [
                'text' => "Vous avez publié une review : " . (mb_strimwidth($review->content, 0, 40, '...')),
                'type' => 'review',
                'created_at' => $review->created_at,
            ];
        }
        // Comments
        foreach ($user->comments()->latest()->limit(10)->get() as $comment) {
            $activities[] = [
                'text' => "Vous avez commenté : " . (mb_strimwidth($comment->content, 0, 40, '...')),
                'type' => 'commentaire',
                'created_at' => $comment->created_at,
            ];
        }
        // Réactions sur les reviews
        foreach ($user->reviewReactions()->latest()->limit(10)->get() as $reaction) {
            $review = $reaction->review;
            $type = $reaction->reaction === 'like' ? 'aimé' : 'pas aimé';
            $activities[] = [
                'text' => "Vous avez $type une review : " . (mb_strimwidth($review ? $review->content : '', 0, 40, '...')),
                'type' => 'réaction',
                'created_at' => $reaction->created_at,
            ];
        }
        // Tri par date décroissante
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        return response()->json($activities);
    }
}
