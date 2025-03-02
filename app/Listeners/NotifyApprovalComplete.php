<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\ApprovalActionPerformed;
use App\Events\ApprovalCompleted;
use App\Events\ApprovalStepChanged;
use Illuminate\Support\Facades\Log;

class NotifyApprovalComplete
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
        $creator = $approvable->creator; // Assuming you have a creator relationship

        if ($creator) {
            $creator->notify(new \App\Notifications\ApprovalComplete(
                $event->instance,
                $event->finalStatus
            ));
        }
    }
}
