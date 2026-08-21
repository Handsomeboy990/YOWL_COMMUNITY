<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    /**
     * Sign in and issue a token.
     *
     * "Se souvenir de moi" was sent by the form and read by nobody: the token
     * lasted the same fourteen days either way, so ticking the box or not made
     * no difference at all. It now decides the lifetime, and the client keeps
     * the token accordingly.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $remember = $request->boolean('remember');

        // Sans la case, la session dure la journee : sur un poste partage,
        // c'est le comportement attendu.
        $expiresAt = $remember
            ? now()->addMinutes((int) config('sanctum.expiration', 20160))
            : now()->addDay();

        $token = $request->user()->createToken('yowl-access-token', ['*'], $expiresAt);

        $user = $request->user();

        // Le compteur avance ici, une fois la connexion acquise : compté avant,
        // une salve de mauvais mots de passe épuiserait le délai de grâce sans
        // que personne ne soit jamais entré.
        $user->increment('login_count');

        $user['roles'] = $user->getRoleNames();

        return response()->json([
            'success' => true,
            'token' => $token->plainTextToken,
            'remember' => $remember,
            'expires_at' => $expiresAt,
            'user' => $user,
            // De quoi afficher le rappel, et dire combien de fois il reste
            // possible de le remettre à plus tard. Sans ce compte à rebours,
            // le blocage arrive sans prévenir.
            'verification' => $user->etatDeVerification(),
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur déconnecté.',
        ]);
    }
}
