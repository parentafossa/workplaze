<?php

namespace App\Filament\Clusters\DataOn\Resources\DataonIFAttLogResource\Pages;

use App\Filament\Clusters\DataOn\Resources\DataonIFAttLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Widgets\AttLogAnalysisWidget;
class ManageDataonIFAttLogs extends ManageRecords
{
    protected static string $resource = DataonIFAttLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

        protected function getHeaderWidgets(): array
    {
        return [
            AttLogAnalysisWidget::class,
        ];
    }
}
