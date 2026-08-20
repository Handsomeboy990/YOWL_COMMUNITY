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

// La racine de l'API. On l'ouvre pour verifier que le service repond : autant
// que la reponse le dise, et pointe vers le site pour qui s'est trompe
// d'adresse. Hors du groupe web comme le reste : elle n'a besoin d'aucune
// session, et c'est ce qui la faisait tomber.
Route::get('/', function () {
    return response()->json([
        'service' => config('app.name').' API',
        'status' => 'ok',
        'site' => config('app.frontend_url'),
        'documentation' => url('/api/health'),
    ]);
});

// Ce que l'application est reellement en train d'utiliser. Protege par le
// meme jeton que le reveil : ces valeurs ne sont pas des secrets, mais elles
// decrivent la pile et n'ont rien a faire en acces libre.
Route::get('/diagnostic/{token}', \App\Http\Controllers\DiagnosticController::class);

Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);

// Le jeton est dans l'adresse : la plupart des services de cron gratuits ne
// savent envoyer qu'une adresse, pas un en-tête.
Route::post('/cron/{token}', CronController::class)->middleware('throttle:20,1');
