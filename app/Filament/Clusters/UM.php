<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

class UM extends Cluster
{
    protected static ?string $navigationIcon = 'fontisto-truck';
    protected static ?string $clusterBreadcrumb = 'Unit Mandiri';
    protected static ?string $navigationLabel = 'Unit Mandiri';
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            //->topNavigation()
            ;
    }

}
