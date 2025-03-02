<?php

namespace App\Traits;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Str;

trait HasResourcePageShield
{
    use HasPageShield;

    public static function getPermissionName(): string
    {
        $resource = static::$resource;
        $resourceName = Str::of(class_basename($resource))
            ->beforeLast('Resource')
            ->snake();
            
        // Convert to reg::letternumber format
        $permissionIdentifier = Str::of($resourceName)
            ->replace('_', '::')
            ->lower();
            
        // Get action from the page class name (CreateRegLetternumber -> create)
        $action = Str::of(class_basename(static::class))
            ->before(class_basename($resource))
            ->lower();
            
        // Construct final permission: create_reg::letternumber
        $permissionName = "{$action}_{$permissionIdentifier}";
        
        \Log::info('Resource page permission construction:', [
            'resource_class' => $resource,
            'resource_name' => $resourceName,
            'action' => $action,
            'permission_name' => $permissionName
        ]);
        
        return $permissionName;
    }
}