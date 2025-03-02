<?php

namespace App\Filament\Clusters\GA\Resources\TrelloCardResource\Pages;

use App\Filament\Clusters\GA\Resources\TrelloCardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTrelloCard extends CreateRecord
{
    protected static string $resource = TrelloCardResource::class;
}
