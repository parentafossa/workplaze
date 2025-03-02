<?php

namespace App\Services;  

use App\Models\ApprovalFlow;
use App\Models\ApprovalInstance;
use App\Models\User;
use App\Notifications\ApprovalAssigned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class ApprovalService
{
    public function initiateApproval(Model $model, ?ApprovalFlow $flow = null)
    {
        if (!$flow) {
            $flow = ApprovalFlow::where('model_type', get_class($model))
                               ->where('is_active', true)
                               ->first();
        }

        if (!$flow) {
            throw new \Exception('No active approval flow found for this model type');
        }

        $instance = $model->approvalInstances()->create([
            'approval_flow_id' => $flow->id,
            'current_step' => 0,
            'status' => 'pending'
        ]);

        // Notify initial approvers
        $this->notifyNextApprovers($instance);

        return $instance;
    }

    public function processApproval(Model $model, $user, string $action, ?string $comments = null, ?string $submitType = null)
    {
        $instance = $model->currentApprovalInstance();
        if (!$instance) {
            throw new \Exception('No active approval instance found');
        }

        $flow = $instance->approvalFlow;
        $steps = $flow->steps;
        $currentStep = $steps[$instance->current_step];

        if (!$this->isAuthorizedApprover($user, $currentStep)) {
            throw new \Exception('Unauthorized approver');
        }

        // Validate action based on step type
        if (!$this->isValidAction($action, $currentStep, $submitType)) {
            throw new \Exception('Invalid action for current step');
        }

        // Create approval action
        $instance->actions()->create([
            'user_id' => $user->emp_id,
            'action' => $action,
            'submit_type' => $submitType,
            'comments' => $comments,
            'step_number' => $instance->current_step
        ]);

        // Process the action
        switch ($action) {
            case 'submit':
                $this->handleSubmitAction($instance, $submitType, $steps);
                break;

            case 'approve':
                $this->handleApproveAction($instance, $currentStep, $steps);
                break;

            case 'reject':
                $this->handleRejectAction($instance, $steps);
                break;
        }

        $instance->save();

        // Notify next approvers if status is still pending
        if (in_array($instance->status, ['pending', 'pending_cancellation'])) {
            $this->notifyNextApprovers($instance);
        }

        return $instance;
    }

    private function handleSubmitAction(ApprovalInstance $instance, ?string $submitType, array $steps)
    {
        $instance->current_step++;

        if ($instance->current_step >= count($steps)) {
            $instance->status = $submitType === 'submit_for_cancellation' ? 'cancelled' : 'completed';
        } else {
            $instance->status = $submitType === 'submit_for_cancellation' ? 'pending_cancellation' : 'pending';
        }
    }

    private function handleApproveAction(ApprovalInstance $instance, array $currentStep, array $steps)
    {
        if ($instance->status === 'pending_cancellation') {
            $instance->status = 'cancelled';
            return;
        }

        if ($this->isStepComplete($instance, $currentStep)) {
            $instance->current_step++;
            
            if ($instance->current_step >= count($steps)) {
                $instance->status = 'completed';
            }
        }
    }

    private function handleRejectAction(ApprovalInstance $instance, array $steps)
    {
        if ($instance->status === 'pending_cancellation') {
            // Find the last non-cancellation step
            $previousStep = $instance->current_step - 1;
            while ($previousStep >= 0) {
                if ($steps[$previousStep]['step_type'] !== 'submit') {
                    break;
                }
                $previousStep--;
            }
            $instance->current_step = max(0, $previousStep);
            $instance->status = 'pending';
        } else {
            $instance->current_step = max(0, $instance->current_step - 1);
            $instance->status = 'rejected';
        }
    }

    private function isValidAction(string $action, array $stepConfig, ?string $submitType = null): bool
    {
        $stepType = $stepConfig['step_type'] ?? 'approve';

        if ($stepType === 'submit') {
            if ($action !== 'submit') {
                return false;
            }
            
            $validSubmitTypes = $stepConfig['submit_options'] ?? ['submit_for_approval'];
            return in_array($submitType, $validSubmitTypes);
        }

        return in_array($action, ['approve', 'reject']);
    }

    public function isAuthorizedApprover($user, array $stepConfig): bool
    {
        return collect($stepConfig['approvers'])->contains(function ($approver) use ($user) {
            return (
                ($approver['type'] === 'user' && $approver['id'] === $user->emp_id) ||
                ($approver['type'] === 'role' && $user->hasRole($approver['role'])) ||
                ($approver['type'] === 'department_head' && $user->isDepartmentHead($approver['department_id']))
            );
        });
    }

    private function isStepComplete(ApprovalInstance $instance, array $stepConfig): bool
    {
        $stepActions = $instance->actions()
            ->where('step_number', $instance->current_step)
            ->where('action', 'approve')
            ->get();

        return $stepConfig['approval_type'] === 'OR' ? 
            $stepActions->isNotEmpty() :
            count($stepActions) >= count($stepConfig['approvers']);
    }

    private function notifyNextApprovers(ApprovalInstance $instance): void
    {
        if (!in_array($instance->status, ['pending', 'pending_cancellation'])) {
            return;
        }

        $currentStep = $instance->approvalFlow->steps[$instance->current_step];
        $stepName = $currentStep['name'];

        foreach ($currentStep['approvers'] as $approverConfig) {
            $approvers = $this->getApproversFromConfig($approverConfig);
            foreach ($approvers as $approver) {
                $approver->notify(new ApprovalAssigned($instance, $stepName));
            }
        }
    }

    private function getApproversFromConfig(array $approverConfig): array
    {
        switch ($approverConfig['type']) {
            case 'user':
                $user = User::where('emp_id', $approverConfig['id'])->first();
                return $user ? [$user] : [];

            case 'role':
                return User::role($approverConfig['role'])->get()->all();

            case 'department_head':
                return User::whereHas('employeeInfo', function ($query) use ($approverConfig) {
                    $query->where('organization_id', $approverConfig['department_id'])
                          ->where('is_department_head', true);
                })->get()->all();

            default:
                return [];
        }
    }
}