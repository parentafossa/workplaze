<?php

namespace App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource\Pages;

use App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListD365VoucherTransactions extends ListRecords
{
    protected static string $resource = D365VoucherTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
