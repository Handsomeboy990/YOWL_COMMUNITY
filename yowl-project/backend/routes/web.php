<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Referencement. Servis par l'API plutot que deposes en fichiers statiques :
// ils dependent d'un reglage et du contenu publie.
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots']);
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap']);

// Reveil planifie, appele par une horloge exterieure. Le jeton est dans
// l'adresse plutot que dans un en-tete : la plupart des services de cron
// gratuits ne savent envoyer qu'une adresse.
Route::post('/cron/{token}', \App\Http\Controllers\CronController::class)
    ->middleware('throttle:20,1');
