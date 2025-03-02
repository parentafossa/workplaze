<?php

namespace App\Filament\Clusters\Settings\Resources\ApprovalFlowResource\Pages;

use App\Filament\Clusters\Settings\Resources\ApprovalFlowResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class EditApprovalFlow extends EditRecord
{
    use HasPageShield;
    
    protected static string $resource = ApprovalFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
