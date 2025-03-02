<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Belongsto;

class Employee extends Model
{
    protected $table = 'emp_information';

    // Optional: define the primary key if it's different from 'id'
    protected $primaryKey = 'emp_id';

    // Disable timestamps if the table does not have `created_at` and `updated_at`
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    public function scopeForCurrentCompany($query)
    {
        $user = Auth::user();
        if ($user && $user->emp_id) {
            $userCompanyId = Employee::where('emp_id', $user->emp_id)->value('company_id');
            return $query->where('company_id', $userCompanyId)
                ->where('active',1);
        }
        return $query;
    }

    public function scopeActiveInCompany($query)
    {
        return $query->forCurrentCompany();
        // Note: active scope is already applied globally
    }    

    public function salesOpportunities(): HasMany
    {
        return $this->hasMany(SalesOpportunity::class, 'emp_id', 'emp_id');
    }

    public function signedQuotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'signee', 'emp_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
