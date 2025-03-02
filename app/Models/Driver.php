<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Driver extends Model
{
    use HasFactory;
    // Set the table that this model references
    protected $table = 'emp_information';

    // Optional: define the primary key if it's different from 'id'
    protected $primaryKey = 'emp_id';

    // Disable timestamps if the table does not have `created_at` and `updated_at`
    public $timestamps = false;

    // Global Scope to filter drivers based on job_code
    protected static function booted()
    {
        static::addGlobalScope('job_code_filter', function (Builder $builder) {
            $builder->whereIn('job_code', ['DVP', 'DVC'])->where('active',1);
        });
    }
}
