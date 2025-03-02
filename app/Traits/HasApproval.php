<?php

namespace App\Traits;

use App\Models\ApprovalFlow;
use App\Models\ApprovalInstance;


trait HasApproval
{
    public function getApprovalStatusAttribute()
    {
        return $this->currentApprovalInstance()?->status ?? 'draft';
    }

    public function isLocked()
    {
        $currentInstance = $this->currentApprovalInstance();
        return $currentInstance && in_array($currentInstance->status, ['pending', 'pending_cancellation']);
    }

    public function isDraft()
    {
        return $this->approval_status === 'draft';
    }

    public function isPending()
    {
        return in_array($this->approval_status, ['pending', 'pending_cancellation']);
    }

    public function isPendingCancellation()
    {
        return $this->approval_status === 'pending_cancellation';
    }

    public function isCompleted()
    {
        return $this->approval_status === 'completed';
    }

    public function isCancelled()
    {
        return $this->approval_status === 'cancelled';
    }

    public function isRejected()
    {
        return $this->approval_status === 'rejected';
    }

    public function canBeEdited()
    {
        return $this->isDraft() || $this->isRejected();
    }

    public function canBeSubmitted()
    {
        return $this->isDraft();
    }

    public function canBeCancelled()
    {
        return $this->isPending() && !$this->isPendingCancellation();
    }

    public function getCurrentApproverNamesAttribute()
    {
        $instance = $this->currentApprovalInstance();
        if (!$instance || !$instance->isPending()) {
            return null;
        }

        $currentStep = $instance->approvalFlow->steps[$instance->current_step] ?? null;
        if (!$currentStep) {
            return null;
        }

        $approverNames = collect($currentStep['approvers'])->map(function ($approver) {
            if ($approver['type'] === 'user') {
                return \App\Models\Employee::where('emp_id', $approver['id'])->value('emp_name');
            } elseif ($approver['type'] === 'role') {
                return 'Role: ' . ucfirst($approver['role']);
            } elseif ($approver['type'] === 'department_head') {
                $dept = \App\Models\Organization::find($approver['department_id']);
                return 'Department Head: ' . ($dept?->name ?? 'Unknown Department');
            }
            return null;
        })->filter()->implode(', ');

        return $approverNames ?: 'No approvers configured';
    }

    public function getNextApproverNamesAttribute()
    {
        $instance = $this->currentApprovalInstance();
        if (!$instance || !$instance->isPending()) {
            return null;
        }

        $nextStepIndex = $instance->current_step + 1;
        $nextStep = $instance->approvalFlow->steps[$nextStepIndex] ?? null;
        if (!$nextStep) {
            return null;
        }

        $approverNames = collect($nextStep['approvers'])->map(function ($approver) {
            if ($approver['type'] === 'user') {
                return \App\Models\Employee::where('emp_id', $approver['id'])->value('emp_name');
            } elseif ($approver['type'] === 'role') {
                return 'Role: ' . ucfirst($approver['role']);
            } elseif ($approver['type'] === 'department_head') {
                $dept = \App\Models\Organization::find($approver['department_id']);
                return 'Department Head: ' . ($dept?->name ?? 'Unknown Department');
            }
            return null;
        })->filter()->implode(', ');

        return $approverNames ?: 'No next approvers configured';
    }

    public function getApprovalHistoryAttribute()
    {
        return $this->approvalInstances()
            ->with(['actions' => function ($query) {
                $query->with(['user.employeeInfo'])
                    ->latest();
            }])
            ->latest()
            ->get();
    }

    public function approvalInstances()
    {
        return $this->morphMany(ApprovalInstance::class, 'approvable');
    }
    public function approvalFlow()
    {
        return $this->belongsTo(ApprovalFlow::class);
    }
    public function currentApprovalInstance()
    {
        return $this->approvalInstances()->latest()->first();
    }
}