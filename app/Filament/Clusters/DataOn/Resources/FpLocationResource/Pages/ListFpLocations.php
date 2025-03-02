<?php

namespace App\Filament\Clusters\DataOn\Resources\FpLocationResource\Pages;

use App\Filament\Clusters\DataOn\Resources\FpLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFpLocations extends ListRecords
{
    protected static string $resource = FpLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
