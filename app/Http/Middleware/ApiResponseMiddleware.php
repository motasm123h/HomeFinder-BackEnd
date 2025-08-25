<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

class ApiResponseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->isSuccessful()) {
            return response()->json([
                'success' => true,
                'data' => $response->getOriginalContent(),
            ]);
        }

        return $response;
    }

    public function terminate($request, $response)
    {
        if (! $response->isSuccessful()) {
            app(LoggerInterface::class)->error('API Error', [
                'status' => $response->status(),
                'path' => $request->path(),
                'error' => $response->getContent(),
            ]);
        }
    }
}
