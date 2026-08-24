<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',

        using: function () {
            Route::namespace('App\Http\Controllers')->group(function () {
                        /*
                        |--------------------------------------------------------------------------
                        | API Routes
                        |--------------------------------------------------------------------------
                        */
                        Route::prefix('api')
                            ->middleware(['api', 'maintenance'])
                            ->group(base_path('routes/api.php'));


                        /*
                        |--------------------------------------------------------------------------
                        | Admin Routes
                        |--------------------------------------------------------------------------
                        */
                        Route::prefix('tradex')
                            ->middleware(['web'])
                            ->namespace('Admin')
                            ->name('admin.')
                            ->group(base_path('routes/admin.php'));


                        /*
                        |--------------------------------------------------------------------------
                        | User Routes
                        |--------------------------------------------------------------------------
                        */
                        Route::prefix('user')
                            ->middleware([
                                'web',
                                'maintenance',
                                'user.legacy-page',
                                'user.spa-response',
                            ])
                            ->group(base_path('routes/user.php'));


                        /*
                        |--------------------------------------------------------------------------
                        | Web Routes (Landing / Public Pages)
                        |--------------------------------------------------------------------------
                        */
                        Route::middleware(['web', 'maintenance'])
                            ->group(base_path('routes/web.php'));
            });
        }
    )

    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Append to Web Middleware Group
        |--------------------------------------------------------------------------
        */

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Middleware Aliases
        |--------------------------------------------------------------------------
        */

        $middleware->alias([

            // Core
            'auth.basic'       => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'can'              => \Illuminate\Auth\Middleware\Authorize::class,
            'auth'             => \App\Http\Middleware\Authenticate::class,
            'guest'            => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed'           => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle'         => \Illuminate\Routing\Middleware\ThrottleRequests::class,

            // Custom Project Middleware
            'admin'            => \App\Http\Middleware\RedirectIfNotAdmin::class,
            'admin.guest'      => \App\Http\Middleware\RedirectIfAdmin::class,
            'maintenance'      => \App\Http\Middleware\MaintenanceMode::class,

            'delete.status'      => \App\Http\Middleware\DeleteStatusMiddleware::class,
            'user.legacy-page'   => \App\Http\Middleware\RedirectLegacyUserPage::class,
            'user.spa-response'  => \App\Http\Middleware\NegotiateUserSpaResponse::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (\Illuminate\Http\Request $request, \Throwable $exception): bool =>
                ($request->is('user/*') && $request->header('X-User-Spa') === '1')
                || $request->expectsJson()
        );
    })

    ->create();
