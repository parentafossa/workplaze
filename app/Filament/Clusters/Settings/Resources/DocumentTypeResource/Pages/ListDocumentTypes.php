<?php

namespace App\Filament\Clusters\Settings\Resources\DocumentTypeResource\Pages;

use App\Filament\Clusters\Settings\Resources\DocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentTypes extends ListRecords
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
