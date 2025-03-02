<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\ApprovalActionPerformed;
use App\Events\ApprovalCompleted;
use App\Events\ApprovalStepChanged;
use Illuminate\Support\Facades\Log;

class UpdateModelStatus
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
    public function handle(ApprovalCompleted $event)
    {
        $approvable = $event->instance->approvable;
        
        // Update the model's status based on approval outcome
        if (method_exists($approvable, 'updateStatusAfterApproval')) {
            $approvable->updateStatusAfterApproval($event->finalStatus);
        }
    }
}
