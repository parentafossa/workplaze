<?php

namespace App\Filament\Clusters\UM\Resources\DriverLogResource\Pages;

use App\Filament\Clusters\UM\Resources\DriverLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriverLog extends EditRecord
{
    protected static string $resource = DriverLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
