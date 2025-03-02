<?php

namespace App\Filament\Clusters\UM\Resources\DriverTripResource\Pages;

use App\Filament\Clusters\UM\Resources\DriverTripResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDriverTrips extends ListRecords
{
    protected static string $resource = DriverTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
