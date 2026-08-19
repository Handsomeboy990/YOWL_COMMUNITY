<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DigestController extends Controller
{
    /**
     * Unsubscribe from the weekly digest with one click and no login.
     *
     * Asking somebody to sign in to stop receiving mail is how a digest turns
     * into spam. The token identifies the account and does nothing else.
     */
    public function unsubscribe(string $token)
    {
        $user = User::where('digest_token', $token)->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien de désabonnement n\'est plus valable.',
            ], 404);
        }

        $user->forceFill(['digest_optin' => false])->saveQuietly();

        return response()->json([
            'success' => true,
            'message' => 'Tu ne recevras plus le résumé hebdomadaire.',
        ]);
    }

    /**
     * Read or change the preference from the application.
     */
    public function update(Request $request)
    {
        $validated = $request->validate(['digest_optin' => 'required|boolean']);

        $request->user()->forceFill(['digest_optin' => $validated['digest_optin']])->saveQuietly();

        return response()->json([
            'success' => true,
            'data' => ['digest_optin' => $validated['digest_optin']],
            'message' => $validated['digest_optin']
                ? 'Tu recevras le résumé chaque semaine.'
                : 'Résumé hebdomadaire désactivé.',
        ]);
    }
}
