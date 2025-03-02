<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $table = 'm_companies';

    // Optional: define the primary key if it's different from 'id'
    protected $primaryKey = 'id';

    // Disable timestamps if the table does not have `created_at` and `updated_at`
    //public $timestamps = false;    //
    public $incrementing = false;
    protected $keyType = 'string';

    public function quotations(): HasMany
    {
        return $this->hasMany(SalesOpportunity::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'company_id', 'company_id');
    }

    public function fpmachines(): HasMany
    {
        return $this->hasMany(FpLocation::class, 'company_id', 'company_id');
    }
}
