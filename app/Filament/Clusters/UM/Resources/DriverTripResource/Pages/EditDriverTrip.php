<?php

namespace App\Filament\Clusters\UM\Resources\DriverTripResource\Pages;

use App\Filament\Clusters\UM\Resources\DriverTripResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriverTrip extends EditRecord
{
    protected static string $resource = DriverTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
