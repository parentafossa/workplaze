<?php

namespace App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource\Pages;

use App\Filament\Clusters\D365\Resources\GwmsDeliveryNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGwmsDeliveryNote extends CreateRecord
{
    protected static string $resource = GwmsDeliveryNoteResource::class;
}
