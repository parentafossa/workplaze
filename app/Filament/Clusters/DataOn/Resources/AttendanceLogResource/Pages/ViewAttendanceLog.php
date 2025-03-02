<?php

namespace App\Filament\Clusters\DataOn\Resources\AttendanceLogResource\Pages;

use App\Filament\Clusters\DataOn\Resources\AttendanceLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceLog extends ViewRecord
{
    protected static string $resource = AttendanceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
