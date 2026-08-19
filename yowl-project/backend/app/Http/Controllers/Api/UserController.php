<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use App\Mail\EmailVerificationCode;
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
                $previous = $user->picture;
                $validated['picture'] = $request->file('picture')->store('profile', 'public');
                if ($previous) {
                    Storage::disk('public')->delete($previous);
                }
            }

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($request->string('password'));
            }

            // Changer d'adresse annule la verification : sans cela, on
            // s'attribuait une adresse qu'on ne controle pas, deja marquee
            // comme verifiee, et les emails de service partaient dessus.
            $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;

            $user->update($validated);

            if ($emailChanged) {
                // email_verified_at n'est pas assignable en masse : passer par
                // update() l'aurait ignore en silence, et la nouvelle adresse
                // serait restee marquee comme verifiee.
                $code = (string) random_int(100000, 999999);
                $user->forceFill([
                    'email_verified_at' => null,
                    'email_verification_code' => $code,
                    'email_verification_expires_at' => now()->addMinutes(15),
                ])->save();

                Mail::to($user->email)->send(new EmailVerificationCode($code));
            }

            $user['roles'] = $user->getRoleNames();

            return response()->json([
                'success' => true,
                'data' => $user,
                'email_verification_required' => $emailChanged,
                'message' => $emailChanged
                    ? 'Profil mis à jour. Un code de vérification a été envoyé à ta nouvelle adresse.'
                    : 'Profil mis à jour avec succès',
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

        // La suppression se faisait par un simple is_active a false : l'adresse,
        // le pseudo, le nom, la photo et la date de naissance restaient en base
        // indefiniment, alors que le produit annonce une suppression.
        //
        // Les donnees personnelles partent, les contributions restent
        // rattachees a un compte anonyme pour ne pas trouer les discussions
        // des autres membres.
        $avatar = $user->picture;

        $user->forceFill([
            'username' => 'membre-supprime-'.$user->id,
            'fullname' => 'Membre supprimé',
            'email' => 'supprime-'.$user->id.'@yowl.invalid',
            'picture' => null,
            'birthdate' => null,
            'password' => Hash::make(Str::random(40)),
            'email_verified_at' => null,
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'remember_token' => null,
            'is_active' => false,
            'anonymized_at' => now(),
        ])->save();

        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }

        $user->tokens()->delete();
        $user->notifications()->delete();
        $user->pushSubscriptions()->delete();
        $user->syncRoles([]);

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé. Tes données personnelles ont été effacées.',
        ]);
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
