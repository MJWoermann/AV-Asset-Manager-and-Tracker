<?php

use App\Http\Middleware\ApplyScannerSessionLifetime;
use App\Http\Middleware\EnsureDeveloperFeature;
use App\Http\Middleware\EnsureUserHasFeature;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SyncScannerSessionLifetimeHint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Plain integer TTL hint — not a secret; must be readable before StartSession.
        $middleware->encryptCookies(except: [
            ApplyScannerSessionLifetime::COOKIE,
        ]);

        $middleware->web(
            prepend: [
                ApplyScannerSessionLifetime::class,
            ],
            append: [
                SyncScannerSessionLifetimeHint::class,
            ],
        );

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'feature' => EnsureUserHasFeature::class,
            'developer_feature' => EnsureDeveloperFeature::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('import/*') || $request->routeIs('import.*')) {
                return redirect()
                    ->route('import.create')
                    ->withErrors(['file' => 'The file is too large. Maximum size is 25MB.']);
            }
        });
    })->create();
