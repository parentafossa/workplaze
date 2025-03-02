<?php

namespace App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource\Pages;

use App\Filament\Clusters\D365\Resources\D365VoucherTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateD365VoucherTransaction extends CreateRecord
{
    protected static string $resource = D365VoucherTransactionResource::class;
}
