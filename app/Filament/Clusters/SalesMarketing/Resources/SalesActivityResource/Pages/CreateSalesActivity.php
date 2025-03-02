<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesActivityResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\SalesActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesActivity extends CreateRecord
{
    protected static string $resource = SalesActivityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }    
}
