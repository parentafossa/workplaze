<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCenter extends Model
{
    protected $table = 'm_costcenters';

    // Optional: define the primary key if it's different from 'id'
    protected $primaryKey = 'id';

    // Disable timestamps if the table does not have `created_at` and `updated_at`
    public $timestamps = false;    //
    public $incrementing = false;
    protected $keyType = 'string';    //

    protected $fillable = [
        'id',
        'name',
        'company_id',
        'segment',
    ];
}
