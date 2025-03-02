<?php

namespace App\Filament\Clusters\SalesMarketing\Resources\DocumentTagResource\Pages;

use App\Filament\Clusters\SalesMarketing\Resources\DocumentTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentTag extends EditRecord
{
    protected static string $resource = DocumentTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
