<?php

namespace App\Filament\Clusters\UM\Resources\CashAdvanceRequestResource\Pages;

use App\Filament\Clusters\UM\Resources\CashAdvanceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashAdvanceRequests extends ListRecords
{
    protected static string $resource = CashAdvanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
