<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class AuthDebugMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::info('Auth Process', [
            'path' => $request->path(),
            'method' => $request->method(),
            'auth_check' => auth()->check(),
            'user' => auth()->user(),
            'session_id' => session()->getId(),
            'session_token' => session()->token(),
            'cookies' => $request->cookies->all(),
            'headers' => $request->headers->all()
        ]);

        $response = $next($request);

        Log::info('After Middleware', [
            'auth_check_after' => auth()->check(),
            'user_after' => auth()->user()
        ]);

        return $response;
    }
}