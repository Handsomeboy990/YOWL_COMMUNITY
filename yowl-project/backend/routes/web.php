<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Referencement. Servis par l'API plutot que deposes en fichiers statiques :
// ils dependent d'un reglage et du contenu publie.
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots']);
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap']);
