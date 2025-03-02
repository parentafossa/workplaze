<?php

namespace App\Filament\Clusters;

use App\Filament\Clusters\UM\Resources\CashAdvanceRequestResource;
use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationItem;

class GA extends Cluster
{
    protected static ?string $navigationIcon = 'fontisto-redis';
    protected static ?string $clusterBreadcrumb = 'General Affair';
    protected static ?string $navigationLabel = 'General Affair';
    /*public static function getNavigationItems(): array
    {
        return [
            ...parent::getNavigationItems('GA'),
            NavigationItem::make('Cash Advance')
                ->url(CashAdvanceRequestResource::getUrl())
                ->icon('lineawesome-cash-register-solid')
                ->sort(2)
                // Optional: Add any permission checks
                ->visible(fn (): bool => auth()->user()->can('view', CashAdvanceRequestResource::class))
        ];
    }*/
}
