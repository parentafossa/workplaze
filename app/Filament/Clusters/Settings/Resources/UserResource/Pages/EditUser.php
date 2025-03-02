<?php

namespace App\Filament\Clusters\Settings\Resources\UserResource\Pages;

use App\Filament\Clusters\Settings\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class EditUser extends EditRecord
{
    use HasPageShield;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
