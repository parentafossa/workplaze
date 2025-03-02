<?php

namespace App\Filament\Clusters\UM\Resources\TripDistanceResource\Pages;

use App\Filament\Clusters\UM\Resources\TripDistanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTripDistances extends ListRecords
{
    protected static string $resource = TripDistanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
