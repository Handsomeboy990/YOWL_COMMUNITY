<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardKPIController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AppealController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\DataExportController;
use App\Http\Controllers\Api\DigestController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\GrowthController;
use App\Http\Controllers\Api\HelpfulController;
use App\Http\Controllers\Api\LegalPageController;
use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TagFeedController;
use App\Http\Controllers\Api\SuggestionController;
use App\Http\Controllers\Api\ReviewReactionController;
use App\Http\Controllers\Api\CommentReactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user();
    $user['roles'] = $user->getRoleNames();

    return $user;
});

require __DIR__.'/auth.php';

Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{review}', [ReviewController::class, 'show']);
Route::get('/tags', [\App\Http\Controllers\Api\TagController::class, 'index']);

// Un tag est un lieu : il a son adresse, ses chiffres et son fil.
Route::get('/sujets', [TagFeedController::class, 'index']);
Route::get('/sujets/{name}', [TagFeedController::class, 'show']);
Route::get('/sujets/{name}/avis', [TagFeedController::class, 'reviews']);

Route::get('/polls/{poll}', [PollController::class, 'show']);

// Pages legales, lues par tout le monde.
Route::get('/legal/{slug}', [LegalPageController::class, 'show']);

// Profil public d'un membre, cible des mentions.
Route::get('/membres/{username}', [UserController::class, 'publicProfile']);
Route::get('/membres/{username}/avis', [UserController::class, 'publicReviews']);

// Desabonnement du resume, sans connexion : un lien depuis un email.
Route::match(['get', 'post'], '/digest/unsubscribe/{token}', [DigestController::class, 'unsubscribe'])
    ->middleware('throttle:20,1');
Route::get('/comments', [CommentController::class, 'index']);
Route::get('/comments/{comment}', [CommentController::class, 'show']);
Route::get('/kpi', [DashboardKPIController::class, 'getKPI']);

// Formulaire de suggestion, ouvert aux visiteurs et limité en cadence
Route::post('/suggestions', [SuggestionController::class, 'store'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('users/{user}', [UserController::class, 'show']);
  Route::post('users/{user}', [UserController::class, 'update']);
  Route::get('users/{user}/activity', [UserController::class, 'activity']);
  Route::get('users/{user}/reviews', [UserController::class, 'reviews']);
  Route::get('users/{user}/stats', [UserController::class, 'stats']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    // La publication accepte jusqu'a 5 fichiers : cadence resserree.
    Route::post('/reviews', [ReviewController::class, 'store'])->middleware('throttle:20,1');
    Route::post('/reviews/{review}', [ReviewController::class, 'update'])->middleware('throttle:30,1');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    // Route::apiResource('reviews', ReviewController::class);
    // Route::apiResource('comments', CommentController::class);
    Route::post('/comments', [CommentController::class, 'store']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/reviews/{review}/react', [ReviewReactionController::class, 'toggleReaction']);
    Route::post('/comments/{comment}/react', [CommentReactionController::class, 'toggleReaction']);

    // Abonnements aux notifications push
    Route::post('/push/subscribe', [\App\Http\Controllers\Api\PushSubscriptionController::class, 'store']);
    Route::post('/push/unsubscribe', [\App\Http\Controllers\Api\PushSubscriptionController::class, 'destroy']);

    // Centre de notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Abonnements : suivre un membre ou un tag
    Route::get('/follows', [FollowController::class, 'index']);
    Route::post('/follows', [FollowController::class, 'store']);
    Route::delete('/follows', [FollowController::class, 'destroy']);
    Route::get('/follows/suggestions', [FollowController::class, 'suggestions']);

    // Autocompletion des mentions
    Route::get('/members/search', [FollowController::class, 'searchMembers'])->middleware('throttle:120,1');

    // Blocage : la decision individuelle, sans arbitrage
    Route::get('/blocks', [BlockController::class, 'index']);
    Route::post('/blocks/{user}', [BlockController::class, 'store']);
    Route::delete('/blocks/{user}', [BlockController::class, 'destroy']);

    Route::patch('/digest', [DigestController::class, 'update']);

    // Consultee pendant la redaction : le meme lien deja discute ailleurs.
    // Hors du prefixe /reviews, que GET /reviews/{review} capterait.
    Route::get('/liens/existant', [ReviewController::class, 'existingForLink']);

    // Portabilite : partir avec ses donnees, pas seulement les effacer
    Route::get('/mes-donnees', [DataExportController::class, 'summary']);
    Route::get('/mes-donnees/export', [DataExportController::class, 'export'])->middleware('throttle:5,10');

    // Sondages : un avis en forme compacte
    Route::post('/reviews/{review}/poll', [PollController::class, 'store']);
    Route::post('/polls/{poll}/vote', [PollController::class, 'vote'])->middleware('throttle:30,1');

    // Utilite d'un avis, distincte du j'aime
    Route::post('/reviews/{review}/helpful', [HelpfulController::class, 'toggle'])->middleware('throttle:60,1');

    // Avis enregistres
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::get('/bookmarks/ids', [BookmarkController::class, 'ids']);
    Route::post('/bookmarks/{review}', [BookmarkController::class, 'store']);
    Route::delete('/bookmarks/{review}', [BookmarkController::class, 'destroy']);

    // Contestation d'une decision de moderation
    Route::get('/appeals', [AppealController::class, 'mine']);
    Route::post('/appeals', [AppealController::class, 'store'])->middleware('throttle:10,1');

    // Signal de presence, seule source du temps par session
    Route::post('/presence', [GrowthController::class, 'ping'])->middleware('throttle:120,60');

    // Signalement de contenu
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:20,1');
});

// Admin routes
Route::middleware(['auth:sanctum','role:admin'])->prefix('admin')->group(function(){
  Route::patch('/users/{user}/role', [AdminController::class, 'changeUserRole']);
  Route::get('/stats', [AdminController::class, 'stats']);

  // Croissance : les cinq indicateurs du cahier des charges
  Route::get('/croissance', [GrowthController::class, 'index']);
  Route::get('/croissance/export', [GrowthController::class, 'export']);
  Route::get('/users', [AdminController::class, 'users']);
  Route::get('/reviews', [AdminController::class, 'reviews']);
  Route::get('/comments', [AdminController::class, 'comments']);
  Route::delete('/reviews/{review}', [AdminController::class, 'deleteReview']);
  Route::delete('/comments/{comment}', [AdminController::class, 'deleteComment']);
  Route::patch('/users/{user}/ban', [AdminController::class, 'banUser']);
  Route::patch('/users/{user}/unban', [AdminController::class, 'unbanUser']);
  Route::patch('/reviews/{review}/publish', [AdminController::class, 'publishReview']);
  Route::patch('/reviews/{review}/unpublish', [AdminController::class, 'unpublishReview']);

  // File de moderation
  Route::get('/reports', [AdminController::class, 'reports']);
  Route::patch('/reports/{report}', [AdminController::class, 'resolveReport']);

  // Suggestions envoyees par les membres
  Route::get('/suggestions', [AdminController::class, 'suggestions']);
  Route::patch('/suggestions/{suggestion}', [AdminController::class, 'updateSuggestion']);

  // Membres : creation, fiche detaillee, edition, mot de passe
  Route::post('/users', [AdminController::class, 'createUser']);
  Route::get('/users/{user}', [AdminController::class, 'showUser']);
  Route::patch('/users/{user}', [AdminController::class, 'updateUser']);
  Route::post('/users/{user}/password', [AdminController::class, 'regeneratePassword']);

  // File des contestations
  Route::get('/appeals', [AppealController::class, 'index']);
  Route::patch('/appeals/{appeal}', [AppealController::class, 'resolve']);

  // Pages legales : edition, brouillon, publication
  Route::get('/legal', [LegalPageController::class, 'index']);
  Route::get('/legal/{slug}', [LegalPageController::class, 'edit']);
  Route::put('/legal/{slug}', [LegalPageController::class, 'update']);
  Route::delete('/legal/{slug}/draft', [LegalPageController::class, 'discardDraft']);
  Route::post('/legal-images', [LegalPageController::class, 'uploadImage']);

  // Reglages de la plateforme, sans redeploiement
  Route::get('/settings', [SettingController::class, 'index']);
  Route::patch('/settings', [SettingController::class, 'update']);
  Route::get('/audit-log', [SettingController::class, 'auditLog']);

  // Roles et droits
  Route::get('/roles', [RoleController::class, 'index']);
  Route::post('/roles', [RoleController::class, 'store']);
  Route::patch('/roles/{role}', [RoleController::class, 'update']);
  Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
  Route::post('/permissions', [RoleController::class, 'storePermission']);
  Route::patch('/users/{user}/roles', [RoleController::class, 'syncUserRoles']);
});




