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
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $user;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'username' => ['string', 'max:255', 'min:3'],
                'fullname' => ['string', 'max:255', 'min:5'],
                'email' => ['string', 'lowercase', 'email', 'max:255'],
                'picture' => ['nullable', 'file', 'image'],
                'password' => [Rules\Password::defaults()],
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
            return $user;
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->errors(),
                ],
                401,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->getMessage(),
                ],
                502,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $user->is_active = false;
        $user->save();
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success'=>true,'message'=>'User banned']);
    }

    /**
     * Get user activity (reviews, comments)
     */
    public function activity(User $user)
    {
        $activities = [];
        // Reviews
        foreach ($user->reviews()->latest()->limit(10)->get() as $review) {
            $activities[] = [
                'text' => "You posted a review: " . (mb_strimwidth($review->content, 0, 40, '...')),
                'created_at' => $review->created_at,
            ];
        }
        // Comments
        foreach ($user->comments()->latest()->limit(10)->get() as $comment) {
            $activities[] = [
                'text' => "You commented: " . (mb_strimwidth($comment->content, 0, 40, '...')),
                'created_at' => $comment->created_at,
            ];
        }
        // Réactions sur les reviews
        foreach ($user->reviewReactions()->latest()->limit(10)->get() as $reaction) {
            $review = $reaction->review;
            $type = $reaction->reaction === 'like' ? 'liked' : 'disliked';
            $activities[] = [
                'text' => "You $type a review: " . (mb_strimwidth($review ? $review->content : '', 0, 40, '...')),
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
