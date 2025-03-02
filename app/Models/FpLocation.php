<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FpLocation extends Model
{
    
    protected $fillable = [
        'company_id',
        'sn',
        'ip_address',
        'site',
        'location',
        'host_name',
        'active',
    ];

    protected $casts=[
        'active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class,'company_id','id');
    }
    
}
