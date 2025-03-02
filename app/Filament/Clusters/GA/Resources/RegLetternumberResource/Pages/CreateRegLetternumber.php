<?php

namespace App\Filament\Clusters\GA\Resources\RegLetternumberResource\Pages;

use App\Filament\Clusters\GA\Resources\RegLetternumberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Filament\Actions\CreateAction;
use App\Traits\HasResourcePageShield;

class CreateRegLetternumber extends CreateRecord
{
    //use HasPageShield;
    //use HasResourcePageShield;

    protected static string $resource = RegLetternumberResource::class;
    protected static ?string $title = 'New Letter Numbers';
    
    /* public static function getPermissionName(): string
    {
        $resourceClass = static::$resource;
        $resourceBaseName = class_basename($resourceClass);
        $pageClass = static::class;
        $pageBaseName = class_basename($pageClass);
        
        \Log::info('Permission name construction details:', [
            'resource_full_class' => $resourceClass,
            'resource_base_name' => $resourceBaseName,
            'page_full_class' => $pageClass,
            'page_base_name' => $pageBaseName,
            'action' => 'create',  // Since this is a CreateRecord page
            'shield_permission' => 'create_reg::letternumber',
            'hasPageShield_default' => 'page_HasPageShield',
            'cluster' => static::$resource::getCluster(),
        ]);

        return 'create_reg::letternumber';
    }

    public function authorize($action = null, $record = null): bool
    {

        // Let HasPageShield handle the authorization
        return parent::authorize($action, $record);
    } */
    
    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return Action::make('createAnother')
            ->hidden();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
