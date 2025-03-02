<?php

namespace App\Filament\Clusters\Settings\Resources\UserResource\Pages;

use App\Filament\Clusters\Settings\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class CreateUser extends CreateRecord
{
    use HasPageShield;

    protected static string $resource = UserResource::class;
}
