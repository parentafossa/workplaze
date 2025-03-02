<?php

namespace App\Filament\Clusters\GA\Resources\RegContractnumberResource\Pages;

use App\Filament\Clusters\GA\Resources\RegContractnumberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class CreateRegContractnumber extends CreateRecord
{
    //use HasPageShield;
    
    protected static string $resource = RegContractnumberResource::class;
    protected static ?string $title = 'New Contract Numbers';

    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return Action::make('createAnother')
            ->hidden();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
