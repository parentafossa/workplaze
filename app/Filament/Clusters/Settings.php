<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Auth;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'fas-toolbox';

    public static function canViewAny(): bool
    {
        // Check if the current user's role is not allowed to view this resource
        return !Auth::user()->hasRole('API User'); // Replace 'restricted_role' with the specific role you want to restrict
    }
}
