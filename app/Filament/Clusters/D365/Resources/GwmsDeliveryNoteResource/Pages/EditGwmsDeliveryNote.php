<?php

namespace App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource\Pages;

use App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGwmsDeliveryNote extends EditRecord
{
    protected static string $resource = GwmsDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
