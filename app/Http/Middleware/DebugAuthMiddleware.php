<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class DebugAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        
        Log::info('Auth Debug', [
            'path' => $request->path(),
            'session_id' => session()->getId(),
            'is_authenticated' => auth()->check(),
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'session_data' => session()->all(),
        ]);

        return $next($request);
    }
}