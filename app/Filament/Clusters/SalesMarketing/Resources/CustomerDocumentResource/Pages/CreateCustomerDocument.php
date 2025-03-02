<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDocument extends CreateRecord
{
    protected static string $resource = CustomerDocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
