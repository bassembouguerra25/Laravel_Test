<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configure Sanctum middleware for API authentication
        $middleware->statefulApi();
        
        // Register role middleware alias
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'prevent.double.booking' => \App\Http\Middleware\PreventDoubleBooking::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle ModelNotFoundException for API routes
        // Return consistent JSON response when resource is not found
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Extract model name from exception message for better error message
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => ucfirst(strtolower(str_replace('_', ' ', $model))) . ' not found',
                ], 404);
            }
        });

        // Handle NotFoundHttpException for API routes
        // This catches routes that don't exist at all
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Check if it's a model binding issue (route exists but model not found)
                // by checking if the route matches a resource pattern
                $path = $request->path();
                if (preg_match('/api\/(events|tickets|bookings)\/\d+/', $path)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resource not found',
                    ], 404);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Route not found',
                ], 404);
            }
        });

        // Handle ValidationException for API routes (already handled by Laravel, but ensure JSON format)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();
