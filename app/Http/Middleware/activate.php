<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class activate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        // dd($user->verification);
        if ($user && $user->verification) {
            if ($user->verification->activation == 1) {
                return $next($request);
            }
        }

        // If user is not logged in, or no verification record, or activation is not 1
        return response()->json([
            'message' => 'You are not verified yet.'
        ], 403);
    }
}
