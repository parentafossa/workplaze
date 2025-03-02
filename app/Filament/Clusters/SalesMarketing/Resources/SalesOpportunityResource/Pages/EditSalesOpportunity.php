<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource;
use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\RelationManagers\QuotationsRelationManager;
use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\RelationManagers\SalesActivitiesRelationManager;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class EditSalesOpportunity extends EditRecord
{
    protected static string $resource = SalesOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

/*     public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    } */
    public function getRelationManagers(): array
    {
        return [
            QuotationsRelationManager::make([
                'collapsed' => false,
            ]),
            SalesActivitiesRelationManager::make([
                'collapsed' => false,
            ]),
        ];
    }
    
}
