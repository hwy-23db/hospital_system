<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Global middleware - Security headers applied to all responses
        $middleware->append([
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // Web middleware group
        $middleware->web([
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // API middleware group
        // Note: EnsureFrontendRequestsAreStateful is removed because we use token-based auth, not session-based
        // This middleware is only needed for SPAs using cookie-based authentication
        $middleware->api([
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'root_user' => \App\Http\Middleware\RootUserMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Ensure API routes always return JSON, not HTML
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });

        // Customize throttle exception responses for API routes
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? null;
                $seconds = $retryAfter ? (int) $retryAfter : 60;
                $minutes = ceil($seconds / 60);

                return response()->json([
                    'message' => 'Too many attempts. Please try again later.',
                    'errors' => [
                        'email' => [
                            trans('auth.throttle', [
                                'seconds' => $seconds,
                                'minutes' => $minutes,
                            ]),
                        ],
                    ],
                    'retry_after' => $seconds,
                    'retry_after_minutes' => $minutes,
                ], 429)->header('Retry-After', $seconds);
            }
        });
    })->create();
