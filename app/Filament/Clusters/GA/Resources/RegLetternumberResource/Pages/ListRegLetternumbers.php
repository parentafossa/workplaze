<?php

namespace App\Filament\Clusters\GA\Resources\RegLetternumberResource\Pages;

use App\Filament\Clusters\GA\Resources\RegLetternumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ListRegLetternumbers extends ListRecords
{
    
    protected static string $resource = RegLetternumberResource::class;
    //protected static ?string $title = 'Letter Numbers';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
