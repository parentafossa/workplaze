<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\ApprovalActionPerformed;
use App\Events\ApprovalCompleted;
use App\Events\ApprovalStepChanged;
use Illuminate\Support\Facades\Log;

class TrackApprovalMetrics
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
    public function handle(ApprovalStepChanged $event)
    {
        // Track time spent in each step
        if ($event->previousStep !== null) {
            $stepStartTime = $event->instance->actions()
                ->where('step_number', $event->previousStep)
                ->oldest()
                ->first()?->created_at;

            $stepEndTime = now();

            if ($stepStartTime) {
                \App\Models\ApprovalMetric::create([
                    'approval_instance_id' => $event->instance->id,
                    'step_number' => $event->previousStep,
                    'duration_minutes' => $stepStartTime->diffInMinutes($stepEndTime),
                ]);
            }
        }
    }
}
