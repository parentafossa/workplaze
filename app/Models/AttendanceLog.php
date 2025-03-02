<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AttendanceLog extends Model
{
    //
    public $timestamps = false;

    protected $table = 'ea_att_log';
    public $incrementing = false;
    
    protected $fillable = [
        'sn',
        'scan_date',
        'pin',
        'source',
        'inoutmode',
        'hash',
        'timestamp',
    ];
    // Since there are no created_at and updated_at fields

    protected $casts = [
        'scan_date' => 'datetime',
        'timestamp' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'pin', 'emp_id');
    }

    // App\Models\AttendanceLog.php
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
