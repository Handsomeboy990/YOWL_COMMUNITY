<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Review;
use App\Models\Comment;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function changeUserRole(Request $request, User $user)
    {
        $role = $request->input('role');
        if (!in_array($role, ['admin', 'client'])) {
            return response()->json(['success'=>false,'message'=>'Invalid role'], 400);
        }
        // Remove all roles and assign the new one
        $user->syncRoles([$role]);
        return response()->json(['success'=>true,'message'=>"Role updated to $role"]);
    }
    public function banUser(User $user)
    {
        $user->is_active = false;
        $user->save();
        return response()->json(['success'=>true,'message'=>'User banned']);
    }

    public function unbanUser(User $user)
    {
        $user->is_active = true;
        $user->save();
        return response()->json(['success'=>true,'message'=>'User unbanned']);
    }

    public function publishReview(Review $review)
    {
        $review->is_published = true;
        $review->save();
        return response()->json(['success'=>true,'message'=>'Review published']);
    }

    public function unpublishReview(Review $review)
    {
        $review->is_published = false;
        $review->save();
        return response()->json(['success'=>true,'message'=>'Review unpublished']);
    }

    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => User::count(),
                'reviews' => Review::count(),
                'comments' => Comment::count(),
                'latest_reviews' => Review::latest()->take(5)->get(['id','content','created_at']),
            ]
        ]);
    }

    public function users(Request $request)
    {
        $query = User::query();
        if ($search = $request->get('search')) {
            $query->where('username','like',"%$search%")->orWhere('email','like',"%$search%");
        }
        $users = $query->orderByDesc('created_at')->paginate(20);
        // Ajoute les rôles à chaque utilisateur
        $users->getCollection()->transform(function ($user) {
            $user->roles = $user->getRoleNames();
            return $user;
        });
        return response()->json(['success'=>true,'data'=>$users]);
    }

    public function reviews(Request $request)
    {
        $query = Review::with('user');
        if ($search = $request->get('search')) {
            $query->where('content','like',"%$search%");
        }
        $reviews = $query->orderByDesc('created_at')->paginate(20);
        return response()->json(['success'=>true,'data'=>$reviews]);
    }

    public function comments(Request $request)
    {
        $query = Comment::with(['user','review']);
        if ($search = $request->get('search')) {
            $query->where('content','like',"%$search%");
        }
        $comments = $query->orderByDesc('created_at')->paginate(30);
        return response()->json(['success'=>true,'data'=>$comments]);
    }

    public function deleteReview(Review $review)
    {
        $review->delete();
        return response()->json(['success'=>true,'message'=>'Review deleted']);
    }

    public function deleteComment(Comment $comment)
    {
        $comment->delete();
        return response()->json(['success'=>true,'message'=>'Comment deleted']);
    }
}
