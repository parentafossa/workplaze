<?php

namespace App\Filament\Clusters\Settings\Resources\ApprovalFlowResource\Pages;

use App\Filament\Clusters\Settings\Resources\ApprovalFlowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ListApprovalFlows extends ListRecords
{
    use HasPageShield;
    
    protected static string $resource = ApprovalFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
