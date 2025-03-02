<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\ApprovalActionPerformed;
use App\Events\ApprovalCompleted;
use App\Events\ApprovalStepChanged;
use Illuminate\Support\Facades\Log;

class LogApprovalAction
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ApprovalActionPerformed $event)
    {
        $approvable = $event->instance->approvable;
        $modelName = class_basename($event->instance->approvable_type);

        Log::info("Approval action performed", [
            'model' => $modelName,
            'id' => $approvable->id,
            'action' => $event->action,
            'submit_type' => $event->submitType,
            'user' => $event->user->emp_id,
            'step' => $event->instance->current_step,
            'status' => $event->instance->status,
        ]);
    }
}
