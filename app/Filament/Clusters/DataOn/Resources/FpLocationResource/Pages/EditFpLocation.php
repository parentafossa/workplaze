<?php

namespace App\Filament\Clusters\DataOn\Resources\FpLocationResource\Pages;

use App\Filament\Clusters\DataOn\Resources\FpLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFpLocation extends EditRecord
{
    protected static string $resource = FpLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
