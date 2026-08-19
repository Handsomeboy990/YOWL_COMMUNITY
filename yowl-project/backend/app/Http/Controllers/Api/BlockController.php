<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    /**
     * Block a member.
     *
     * Blocking is one sided and cuts the relation both ways: the blocked
     * person disappears from the feed and can no longer follow back. Reporting
     * asks a moderator to judge; blocking is the decision a member takes for
     * themselves, and it needs no approval.
     */
    public function store(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tu ne peux pas te bloquer toi-même.',
            ], 422);
        }

        Block::firstOrCreate([
            'user_id' => $request->user()->id,
            'blocked_id' => $user->id,
        ]);

        // Un blocage rompt l'abonnement dans les deux sens : rester abonne a
        // quelqu'un qu'on bloque n'a pas de sens, et l'inverse non plus.
        Follow::where('followable_type', User::class)
            ->where(function ($query) use ($request, $user) {
                $query->where(fn ($q) => $q->where('user_id', $request->user()->id)->where('followable_id', $user->id))
                    ->orWhere(fn ($q) => $q->where('user_id', $user->id)->where('followable_id', $request->user()->id));
            })
            ->delete();

        return response()->json([
            'success' => true,
            'data' => ['blocked' => true],
            'message' => 'Membre bloqué. Tu ne verras plus ses publications.',
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        Block::where('user_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();

        return response()->json([
            'success' => true,
            'data' => ['blocked' => false],
            'message' => 'Membre débloqué',
        ]);
    }

    public function index(Request $request)
    {
        $blocked = User::whereIn(
            'id',
            Block::where('user_id', $request->user()->id)->select('blocked_id')
        )->get(['id', 'username', 'fullname', 'picture']);

        return response()->json([
            'success' => true,
            'data' => $blocked,
            'message' => 'Blocks retrieved successfully.',
        ]);
    }
}
