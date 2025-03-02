<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\RelationManagers;

class ViewSalesOpportunity extends ViewRecord
{
    protected static string $resource = SalesOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

/*     public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    } */
   
}
