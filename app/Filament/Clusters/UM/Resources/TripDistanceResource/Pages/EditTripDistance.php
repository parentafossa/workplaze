<?php

namespace App\Filament\Clusters\UM\Resources\TripDistanceResource\Pages;

use App\Filament\Clusters\UM\Resources\TripDistanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTripDistance extends EditRecord
{
    protected static string $resource = TripDistanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
