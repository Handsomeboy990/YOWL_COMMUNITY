<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques sans état
|--------------------------------------------------------------------------
|
| Ni cookie, ni session, ni jeton CSRF. Elles vivent hors du groupe web pour
| cette raison : la pile web leur imposait un chiffrement de cookie et un
| démarrage de session dont elles n'ont aucun usage, et une panne de cette
| pile les emportait avec elle.
|
*/

Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);

// Le jeton est dans l'adresse : la plupart des services de cron gratuits ne
// savent envoyer qu'une adresse, pas un en-tête.
Route::post('/cron/{token}', CronController::class)->middleware('throttle:20,1');
