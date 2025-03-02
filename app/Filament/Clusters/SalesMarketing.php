<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use App\Filament\Clusters\SalesMarketing\Resources\CustomerResource;
use Filament\Panel;
class SalesMarketing extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getNavigationGroups(): array
    {
        return parent::getNavigationGroups();
    }


    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->sidebarCollapsible()
            ->collapsibleNavigationGroups(true);
    }
}
