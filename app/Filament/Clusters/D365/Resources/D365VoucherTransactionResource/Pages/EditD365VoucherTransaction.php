<?php

namespace App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource\Pages;

use App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditD365VoucherTransaction extends EditRecord
{
    protected static string $resource = D365VoucherTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
