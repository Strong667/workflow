<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Имя алиаса намеренно отличается от jwt.auth: этот алиас занимает
        // сам пакет tymon/jwt-auth, и его middleware перекрыл бы наш.
        $middleware->alias([
            'jwt.guard' => \App\Http\Middleware\JwtAuthenticate::class,
            'role'      => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Недостаточно прав'], 403);
            }
        });
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Ресурс не найден'], 404);
            }
        });
    })->create();
