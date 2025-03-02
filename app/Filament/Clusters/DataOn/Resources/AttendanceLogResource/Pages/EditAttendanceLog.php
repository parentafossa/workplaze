<?php

namespace App\Filament\Clusters\DataOn\Resources\AttendanceLogResource\Pages;

use App\Filament\Clusters\DataOn\Resources\AttendanceLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceLog extends EditRecord
{
    protected static string $resource = AttendanceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
