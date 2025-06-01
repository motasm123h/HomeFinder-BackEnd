<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            \App\Http\Middleware\CorsMiddleware::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\admin::class,
            'activate' => \App\Http\Middleware\activate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) { // <-- THIS IS THE KEY PART
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found',
                    'data' => null,
                ], 404);
            }
        });

        // Handle your custom ApiException
        // $exceptions->render(function (\App\Exceptions\ApiException $e, Request $request) {
        //     if ($request->expectsJson() || $request->is('api/*')) {
        //         return response()->json([
        //             'message' => $e->getMessage(),
        //             'data' => $e->getData(), // Assuming getData() exists on your ApiException
        //         ], $e->getCode() ?: 500); // Default to 500 if code is 0
        //     }
        // });

        // // Catch all other exceptions as a generic 500 Internal Server Error
        // $exceptions->render(function (Throwable $e, Request $request) {
        //     if ($request->expectsJson() || $request->is('api/*')) {
        //         // Log the error for debugging (you might want more detail in logs)
        //         \Log::error("Unhandled API Exception: " . $e->getMessage(), ['exception' => $e]);

        //         // In production, you might want a generic "Server Error" message
        //         $message = config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.';
        //         $statusCode = $e->getCode();
        //         // Ensure it's a valid HTTP status code, otherwise default to 500
        //         if ($statusCode < 100 || $statusCode >= 600) {
        //             $statusCode = 500;
        //         }

        //         return response()->json([
        //             'message' => $message,
        //             'data' => null,
        //         ], $statusCode);
        //     }
        // });
    })
    ->create();
    // ->withExceptions(function (Exceptions $exceptions) {})->create();
