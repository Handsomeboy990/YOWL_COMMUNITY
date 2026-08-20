<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',

        // robots.txt, le plan de site et le reveil planifie ne portent ni
        // cookie ni session. Les declarer ici plutot que dans routes/web.php
        // les tient hors du groupe web, dont ils n'utilisaient rien et qui
        // les entrainait dans sa chute.
        then: function () {
            Route::middleware('throttle:60,1')
                ->group(__DIR__.'/../routes/public.php');
        },
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        // Le frontend est une application separee : elle presente un jeton
        // bearer, pas un cookie de session.
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pas de EnsureFrontendRequestsAreStateful ici, et c'est voulu.
        //
        // Ce middleware fait basculer toute requete portant un en-tete Origin
        // qui correspond a FRONTEND_URL dans la pile web complete : cookies
        // chiffres, session, verification CSRF. Le client de cette API est une
        // application d'une seule page qui s'authentifie exclusivement par
        // jeton bearer et n'appelle jamais /sanctum/csrf-cookie : elle n'a
        // aucun besoin de cette pile.
        //
        // Le garder rendait le comportement de l'API dependant de la presence
        // d'un en-tete Origin. La meme adresse repondait 200 en ligne de
        // commande et 500 depuis un navigateur, ce qui est un cauchemar a
        // diagnostiquer et n'apportait rien.

        // Render, comme tout hebergement, place l'application derriere un
        // proxy. Sans cette ligne, Laravel croit repondre en HTTP et fabrique
        // des adresses en http:// dans un site servi en https://, que le
        // navigateur bloque ensuite comme contenu mixte.
        $middleware->trustProxies(at: '*');

        // Baseline budget for every API route, defined in AppServiceProvider.
        // Routes that deserve a tighter one declare it themselves.
        $middleware->throttleApi();

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sans cela, une route API appelee sans en-tete Accept recoit une
        // redirection 302 vers une page de connexion qui n'existe pas, au lieu
        // du 401 que tout client attend.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
