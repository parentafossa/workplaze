<?php

namespace App\Http\Middleware;

use Closure;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DebugbarForAdmin
{
    public function handle($request, Closure $next)
    {
        Log::info('test: ' . $request->user());
        
        if (auth()->check() && auth()->user()->isAdmin()) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
        
        return $next($request);
    }
}