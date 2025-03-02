<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerDocuments extends ListRecords
{
    protected static string $resource = CustomerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
