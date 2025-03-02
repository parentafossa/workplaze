<?php

namespace App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource\Pages;

use App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewD365VoucherTransaction extends ViewRecord
{
    protected static string $resource = D365VoucherTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
