<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Skip for admin users
            if ($user->is_admin) {
                return;
            }
            
            $companyIds = $user->getCompanyIds();
            if (!empty($companyIds)) {
                $builder->whereIn('company_id', $companyIds);
            }
        }
    }
}
