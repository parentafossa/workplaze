<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesOpportunities extends ListRecords
{
    protected static string $resource = SalesOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
