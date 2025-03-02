<?php

namespace App\Filament\Clusters\GA\Resources\RegContractnumberResource\Pages;

use App\Filament\Clusters\GA\Resources\RegContractnumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegContractnumbers extends ListRecords
{
    protected static string $resource = RegContractnumberResource::class;
    //protected static ?string $title = 'Contract Numbers';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
