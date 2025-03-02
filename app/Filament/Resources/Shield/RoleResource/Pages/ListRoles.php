<?php

namespace App\Filament\Resources\Shield\RoleResource\Pages;

use App\Filament\Resources\Shield\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ListRoles extends ListRecords
{
    use HasPageShield;

    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
