<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Response;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class CustomAuthorizationHandler
{
    use HasPageShield;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            try {
                // Get the current route
                $route = $request->route();
                
                // Get the middleware from the route
                $middlewares = $route->gatherMiddleware();
                
                // Check if the route has shield protection
                $hasShield = collect($middlewares)->contains(function ($middleware) {
                    return str_contains($middleware, 'shield');
                });

                if ($hasShield) {
                    // Get the permission name from the route
                    $permission = $this->getPermissionFromRoute($route);
                    
                    if (!Auth::user()->can($permission)) {
                        // User doesn't have permission
                        Notification::make()
                            ->title('Access Denied')
                            ->body('You do not have permission to access this resource.')
                            ->danger()
                            ->send();

                        return redirect()->route('filament.admin.pages.dashboard');
                    }
                }

                return $next($request);
            } catch (\Exception $e) {
                // If any error occurs, redirect to dashboard with notification
                Notification::make()
                    ->title('Error')
                    ->body('An error occurred while checking permissions.')
                    ->danger()
                    ->send();

                return redirect()->route('filament.admin.pages.dashboard');
            }
        }

        // If user is not authenticated, redirect to login
        Notification::make()
            ->title('Authentication Required')
            ->body('Please login to access this resource.')
            ->warning()
            ->send();

        return redirect()->route('filament.app.auth.login');
    }

    protected function getPermissionFromRoute($route): string
    {
        // Get the controller and action from the route
        $action = $route->getAction();
        
        if (isset($action['controller'])) {
            $parts = explode('@', class_basename($action['controller']));
            $controller = $parts[0];
            $method = $parts[1] ?? 'index';
            
            // Convert to permission format (e.g., 'view_users')
            return strtolower(sprintf(
                '%s_%s',
                $method,
                str_replace('Controller', '', $controller)
            ));
        }
        
        // Fallback for page resources
        $uri = $route->uri();
        $segments = explode('/', $uri);
        $resource = end($segments);
        
        return sprintf('view_%s', $resource);
    }
}