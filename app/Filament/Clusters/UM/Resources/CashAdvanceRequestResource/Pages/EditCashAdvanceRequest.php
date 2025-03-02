<?php

namespace App\Filament\Clusters\UM\Resources\CashAdvanceRequestResource\Pages;

use App\Filament\Clusters\UM\Resources\CashAdvanceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashAdvanceRequest extends EditRecord
{
    protected static string $resource = CashAdvanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
