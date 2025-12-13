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
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $token = $request->user()->createToken('yowl-access-token');

        $user = $request->user();
        $user['roles'] = $user->getRoleNames();

        // $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        // Auth::guard('web')->logout();

        // $request->session()->invalidate();

        // $request->session()->regenerateToken();

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur déconnecté.',
        ]);
    }
}
