<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ApprovalAction extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'submit_type',
        'comments',
        'step_number'
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(ApprovalInstance::class, 'approval_instance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'emp_id');
    }

    public function getEmployeeAttribute(): ?Employee
    {
        return $this->user?->employeeInfo;
    }
}