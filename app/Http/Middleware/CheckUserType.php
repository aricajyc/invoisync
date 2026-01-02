<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        if (auth()->user()->user_type !== $type) {
            return response()->json([
                'message' => 'This feature is only available for ' . $type . ' users',
            ], 403);
        }

        return $next($request);
    }
}