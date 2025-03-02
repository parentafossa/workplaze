<?php

namespace App\Filament\Clusters\DataOn\Resources\AttendanceLogResource\Pages;

use App\Filament\Clusters\DataOn\Resources\AttendanceLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceLogs extends ListRecords
{
    protected static string $resource = AttendanceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
