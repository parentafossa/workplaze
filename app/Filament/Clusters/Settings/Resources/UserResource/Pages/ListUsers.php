<?php

namespace App\Filament\Clusters\Settings\Resources\UserResource\Pages;

use App\Filament\Clusters\Settings\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ListUsers extends ListRecords
{
    use HasPageShield;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
