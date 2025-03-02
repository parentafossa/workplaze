<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalInstance extends Model
{
    protected $fillable = [
        'approval_flow_id',
        'current_step',
        'status'
    ];

    protected $casts = [
        'current_step' => 'integer',
    ];

    public function approvalFlow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class);
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class);
    }

    public function latestAction(): ?ApprovalAction
    {
        return $this->actions()->latest()->first();
    }

    public function getCurrentStepConfigAttribute(): ?array
    {
        return $this->approvalFlow?->steps[$this->current_step] ?? null;
    }

    public function isPending(): bool
    {
        // Define "pending" statuses
        $pendingStatuses = ['pending', 'pending_cancellation'];

        return in_array($this->status, $pendingStatuses);
    }
}