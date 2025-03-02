<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    protected $instance;
    protected $stepName;

    public function __construct($instance, $stepName)
    {
        $this->instance = $instance;
        $this->stepName = $stepName;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $approvable = $this->instance->approvable;
        $modelName = class_basename($this->instance->approvable_type);
        $status = $this->instance->status === 'pending_cancellation' ? 'Cancellation Request' : 'Approval Request';

        return (new MailMessage)
            ->subject("{$status}: {$this->stepName}")
            ->line("You have been assigned as an approver for {$modelName} #{$approvable->id}")
            ->line("Step: {$this->stepName}")
            ->line("Status: {$status}")
            ->action('View Request', url('/admin'))
            ->line('Please review and take action on this request.');
    }

    public function toArray($notifiable): array
    {
        $approvable = $this->instance->approvable;
        $modelName = class_basename($this->instance->approvable_type);
        $status = $this->instance->status;

        // Determine request type based on status
        $requestType = ($status === 'pending_cancellation') ? 'Cancellation' : 'Approval';
        $approvalType = ($status === 'pending_cancellation') ? 'cancellation approval' : 'approval';

        return [
            'title' => "New {$requestType} Request",
            'message' => "{$modelName} #{$approvable->id} requires your {$approvalType}",
            'step_name' => $this->stepName,
            'approval_instance_id' => $this->instance->id,
            'approvable_type' => $this->instance->approvable_type,
            'approvable_id' => $this->instance->approvable_id,
            'status' => $status
        ];
    }

}