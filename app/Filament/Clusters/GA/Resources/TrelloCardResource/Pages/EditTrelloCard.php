<?php

namespace App\Filament\Clusters\GA\Resources\TrelloCardResource\Pages;

use App\Filament\Clusters\GA\Resources\TrelloCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrelloCard extends EditRecord
{
    protected static string $resource = TrelloCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
