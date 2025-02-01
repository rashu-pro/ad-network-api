<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
//            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Access token is missing or invalid.'
                ], 401);
            }
            return redirect()->guest(route('login'));
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
           if ($request->is('api/*')) {
               return response()->json([
                   'success' => false,
                   'message' => $e->getMessage()
               ],404);
           }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
           if ($request->is('api/*')) {
               return response()->json([
                   'success' => false,
                   'message' => $e->getMessage()
               ],404);
           }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
           if ($request->is('api/*')) {
               return response()->json([
                   'success' => false,
                   'message' => $e->getMessage(),
                   'error' => $e->errors()
               ],404);
           }
        });

        $exceptions->render(function(\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e,Request $request) {
           if ($request->is('api/*')) {
               return response()->json([
                   'success' => false,
                   'message' => $e->getMessage()
               ],500);
           }
        });
        $exceptions->render(function(\Illuminate\Database\Eloquent\ModelNotFoundException $e,Request $request) {
           if ($request->is('api/*')) {
               return response()->json([
                   'success' => false,
                   'message' => "Resource not found."
               ],404);
           }
        });
        $exceptions->render(function(\App\Exceptions\SecureApiException $e,Request $request) {
           if ($request->is('api/*')) {
               return response()->json([
                   'success' => false,
                   'message' => $e->getMessage()
               ],$e->getStatusCode());
           }
        });
    })->create();
