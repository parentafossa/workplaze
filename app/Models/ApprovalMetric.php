<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ApprovalMetric extends Model
{
    protected $fillable = [
        'approval_instance_id',
        'step_number',
        'duration_minutes',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'step_number' => 'integer',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(ApprovalInstance::class, 'approval_instance_id');
    }
}
