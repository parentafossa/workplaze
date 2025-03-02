<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\SalesOpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOpportunity extends CreateRecord
{
    protected static string $resource = SalesOpportunityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
