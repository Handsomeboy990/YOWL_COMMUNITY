<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class PasswordController extends Controller
{
    /**
     * Change your own password, proving you know the current one.
     *
     * The profile form used to accept a new password on its own. Anybody who
     * found a session left open could therefore take the account over and
     * lock its owner out without ever knowing the old password. Asking for it
     * first is what makes a session theft recoverable.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.confirmed' => 'Les deux nouveaux mots de passe ne correspondent pas.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            // 422 plutot que 403 : c'est une saisie a corriger dans le
            // formulaire, pas un droit qui manque.
            return response()->json([
                'success' => false,
                'errors' => ['current_password' => ['Ce mot de passe ne correspond pas à ton mot de passe actuel.']],
                'message' => 'Mot de passe actuel incorrect.',
            ], 422);
        }

        if (Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['password' => ['Choisis un mot de passe différent de l\'actuel.']],
                'message' => 'Le nouveau mot de passe est identique à l\'ancien.',
            ], 422);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        // Les autres sessions tombent. Un changement de mot de passe sert
        // souvent a reprendre la main sur un compte : laisser les autres
        // jetons vivants le viderait de son sens. Le jeton courant survit,
        // sinon on se deconnecterait soi-meme en se protegeant.
        $courant = $request->user()->currentAccessToken();
        $user->tokens()->where('id', '!=', $courant?->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié. Les autres appareils ont été déconnectés.',
        ]);
    }
}
