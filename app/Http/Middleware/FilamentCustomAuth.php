<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Log;

class FilamentCustomAuth extends Middleware
{
    protected function authenticate($request, array $guards)
    {
        $guardName = config('filament.auth.guard');
        $guard = auth()->guard($guardName);

        // If we're already on the login page, don't check authentication
        if ($request->routeIs('filament.admin.auth.login')) {
            return;
        }

        $user = $guard->user();

        Log::info('Filament Auth Check', [
            'path' => $request->path(),
            'is_login_page' => $request->routeIs('filament.admin.auth.login'),
            'auth_check' => $guard->check(),
            'user' => $user ? $user->id : null,
            'is_super_admin' => $user ? $user->hasRole(config('filament-shield.super_admin.name')) : false,
        ]);

        if (!$guard->check()) {
            $this->unauthenticated($request, $guards);
            return;
        }

        return $user;
    }

    public function handle($request, Closure $next, ...$guards)
    {
        try {
            // Skip authentication for login page
            if ($request->routeIs('filament.admin.auth.login')) {
                return $next($request);
            }

            $this->authenticate($request, $guards);
            
            $user = auth()->user();
            
            if ($user && $user->hasRole(config('filament-shield.super_admin.name'))) {
                return $next($request);
            }

            return $next($request);
        } catch (\Exception $e) {
            Log::error('Auth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('filament.admin.auth.login');
        }
    }

    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('filament.admin.auth.login');
        }
    }
}