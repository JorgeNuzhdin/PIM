<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        
        // Alias de middlewares personalizados
        $middleware->alias([
            'can.edit.problemas' => \App\Http\Middleware\CanEditProblemas::class,
            'can.upload.sheets' => \App\Http\Middleware\CanUploadSheets::class,
            'can.upload.metodos' => \App\Http\Middleware\CanUploadMetodos::class,
        ]);
    })
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\IsAdmin::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejar errores 419 (Token CSRF expirado)
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            return redirect()->route('login')->with('error', 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.');
        });
    })->create();