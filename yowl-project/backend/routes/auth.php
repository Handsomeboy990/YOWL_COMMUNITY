<?php

/*
|--------------------------------------------------------------------------
| Routes d'authentification
|--------------------------------------------------------------------------
|
| Pas de middleware « guest » ici, et c'est voulu. Il interroge le garde de
| session pour vérifier que l'appelant n'est pas déjà connecté, ce qui n'a
| aucun sens sur une API qui ne délivre que des jetons : il n'y a pas de
| session à consulter. Il imposait en revanche un magasin de sessions
| fonctionnel, et une table absente faisait répondre 500 à toute connexion,
| avec pour seul indice le mot « Server Error ».
|
*/

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Registration sends an email on every call, so it is metered tightly.
Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('register');

// OTP email verification (code based) - throttled against brute force
use App\Http\Controllers\Auth\EmailOtpController;
Route::post('/email/otp/resend', [EmailOtpController::class, 'resend'])->middleware('throttle:6,1');
Route::post('/email/otp/verify', [EmailOtpController::class, 'verify'])->middleware('throttle:10,1');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->name('login');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('password.store');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');
