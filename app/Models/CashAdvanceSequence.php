<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdvanceSequence extends Model
{
    //
    protected $fillable = [
        'company_id',
        'year',
        'last_number'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
