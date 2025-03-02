<?php

// app/Http/Middleware/SurveyAuthMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('survey')->check()) {
            return redirect()->route('filament.survey.auth.login');
        }

        return $next($request);
    }
}
