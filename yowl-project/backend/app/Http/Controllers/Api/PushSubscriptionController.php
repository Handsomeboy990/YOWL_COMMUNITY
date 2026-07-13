<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Register (or refresh) a push subscription for the authenticated user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:2000',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $validated['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
            ]
        );

        return response()->json(['success' => true, 'message' => 'Abonnement enregistré']);
    }

    /**
     * Remove a push subscription (logout or user opt-out).
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:2000',
        ]);

        PushSubscription::where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Abonnement supprimé']);
    }
}
