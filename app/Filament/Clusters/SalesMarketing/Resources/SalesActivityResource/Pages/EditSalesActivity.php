<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesActivityResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\SalesActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesActivity extends EditRecord
{
    protected static string $resource = SalesActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
