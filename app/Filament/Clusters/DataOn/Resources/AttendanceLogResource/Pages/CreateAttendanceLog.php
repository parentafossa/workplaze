<?php

namespace App\Filament\Clusters\DataOn\Resources\AttendanceLogResource\Pages;

use App\Filament\Clusters\DataOn\Resources\AttendanceLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceLog extends CreateRecord
{
    protected static string $resource = AttendanceLogResource::class;
}
