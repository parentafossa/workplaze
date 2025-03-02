<?php

namespace App\Filament\Clusters\Settings\Resources\ApprovalFlowResource\Pages;

use App\Filament\Clusters\Settings\Resources\ApprovalFlowResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class CreateApprovalFlow extends CreateRecord
{
    use HasPageShield;
    
    protected static string $resource = ApprovalFlowResource::class;
}
