<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShieldPermissionDebugMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            Log::info('Shield Debug: No authenticated user');
            return $next($request);
        }

        // Get the current route
        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        
        // Get Shield configuration
        $config = config('filament-shield');
        
        // Get current path segments to help identify the resource
        $path = $request->path();
        $segments = explode('/', $path);
        
        Log::info('Shield Permission Debug', [
            'user_id' => $user->id,
            'route' => $routeName,
            'path' => $path,
            'path_segments' => $segments,
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'shield_config' => $config,
            'is_super_admin' => $user->hasRole($config['super_admin']['role_name'] ?? 'super_admin'),
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'request_method' => $request->method(),
            'current_permission_check' => $this->guessCurrentPermission($segments, $request->method())
        ]);

        return $next($request);
    }

    protected function guessCurrentPermission(array $segments, string $method): ?string
    {
        // Remove 'app' or admin segment if present
        $segments = array_values(array_filter($segments, fn($segment) => 
            !in_array($segment, ['app', 'admin']))
        );

        if (empty($segments)) {
            return null;
        }

        // Convert segments to permission format
        $resource = Str::slug(end($segments));
        
        // Map HTTP method to permission prefix
        $prefix = match($method) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view'
        };

        // If it's a listing page, use view_any
        if ($method === 'GET' && count($segments) === 1) {
            $prefix = 'view_any';
        }

        return "{$prefix}_{$resource}";
    }
}