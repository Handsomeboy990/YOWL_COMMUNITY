<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardKPIController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ReviewReactionController;
use App\Http\Controllers\Api\CommentReactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__.'/auth.php';

Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{review}', [ReviewController::class, 'show']);
Route::get('/tags', [\App\Http\Controllers\Api\TagController::class, 'index']);

Route::get('/comments', [CommentController::class, 'index']);
Route::get('/comments/{comment}', [CommentController::class, 'show']);
Route::get('/kpi', [DashboardKPIController::class, 'getKPI']);

Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('users/{user}', [UserController::class, 'show']);
  Route::post('users/{user}', [UserController::class, 'update']);
  Route::get('users/{user}/activity', [UserController::class, 'activity']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::post('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    // Route::apiResource('reviews', ReviewController::class);
    // Route::apiResource('comments', CommentController::class);
    Route::post('/comments', [CommentController::class, 'store']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/reviews/{review}/react', [ReviewReactionController::class, 'toggleReaction']);
    Route::post('/comments/{comment}/react', [CommentReactionController::class, 'toggleReaction']);
});

// Admin routes
Route::middleware(['auth:sanctum','role:admin'])->prefix('admin')->group(function(){
  Route::patch('/users/{user}/role', [AdminController::class, 'changeUserRole']);
  Route::get('/stats', [AdminController::class, 'stats']);
  Route::get('/users', [AdminController::class, 'users']);
  Route::get('/reviews', [AdminController::class, 'reviews']);
  Route::get('/comments', [AdminController::class, 'comments']);
  Route::delete('/reviews/{review}', [AdminController::class, 'deleteReview']);
  Route::delete('/comments/{comment}', [AdminController::class, 'deleteComment']);
  Route::patch('/users/{user}/ban', [AdminController::class, 'banUser']);
  Route::patch('/users/{user}/unban', [AdminController::class, 'unbanUser']);
  Route::patch('/reviews/{review}/publish', [AdminController::class, 'publishReview']);
  Route::patch('/reviews/{review}/unpublish', [AdminController::class, 'unpublishReview']);
});




