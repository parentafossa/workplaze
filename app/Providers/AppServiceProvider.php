<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Services\ApprovalService;
use Barryvdh\Debugbar\Facades\Debugbar;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(ApprovalService::class, function ($app) {
            return new ApprovalService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Check if the user is authenticated and has the 'admin' role
                //
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
        Gate::policy(Employee::class, EmployeePolicy::class);
        //Gate::policy(RegLetternumber::class, RegLetternumberPolicy::class);

    }
}
