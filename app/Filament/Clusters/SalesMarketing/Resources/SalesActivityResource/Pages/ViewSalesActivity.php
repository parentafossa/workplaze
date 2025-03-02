<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\SalesActivityResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\SalesActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesActivity extends ViewRecord
{
    protected static string $resource = SalesActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
