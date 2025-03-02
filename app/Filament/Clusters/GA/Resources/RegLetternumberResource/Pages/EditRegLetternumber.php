<?php

namespace App\Filament\Clusters\GA\Resources\RegLetternumberResource\Pages;

use App\Filament\Clusters\GA\Resources\RegLetternumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use App\Traits\HasResourcePageShield;

class EditRegLetternumber extends EditRecord
{
    //use HasPageShield;
    //use HasResourcePageShield;

    protected static string $resource = RegLetternumberResource::class;
    protected static ?string $title = 'Edit Letter Numbers';
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
