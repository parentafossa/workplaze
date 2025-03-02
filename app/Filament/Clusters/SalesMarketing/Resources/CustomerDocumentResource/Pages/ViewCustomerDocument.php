<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\RelationManagers;

class ViewCustomerDocument extends ViewRecord
{
    protected static string $resource = CustomerDocumentResource::class;

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
