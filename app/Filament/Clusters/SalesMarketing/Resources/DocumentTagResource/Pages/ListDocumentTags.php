<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\DocumentTagResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\DocumentTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentTags extends ListRecords
{
    protected static string $resource = DocumentTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
