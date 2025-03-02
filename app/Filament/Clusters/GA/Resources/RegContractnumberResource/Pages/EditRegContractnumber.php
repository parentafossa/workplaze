<?php

namespace App\Filament\Clusters\GA\Resources\RegContractnumberResource\Pages;

use App\Filament\Clusters\GA\Resources\RegContractnumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class EditRegContractnumber extends EditRecord
{
    //Use HasPageShield;
    
    protected static string $resource = RegContractnumberResource::class;
    protected static ?string $title = 'Edit Contract Numbers';
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
