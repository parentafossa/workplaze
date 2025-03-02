<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\CustomerDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDocument extends EditRecord
{
    protected static string $resource = CustomerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
