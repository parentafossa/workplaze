<?php

namespace App\Providers;

use App\Events\ApprovalActionPerformed;
use App\Events\ApprovalCompleted;
use App\Events\ApprovalStepChanged;
use App\Listeners\LogApprovalAction;
use App\Listeners\NotifyApprovalComplete;
use App\Listeners\UpdateModelStatus;
use App\Listeners\TrackApprovalMetrics;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ApprovalActionPerformed::class => [
            LogApprovalAction::class,
        ],
        ApprovalCompleted::class => [
            NotifyApprovalComplete::class,
            UpdateModelStatus::class,
        ],
        ApprovalStepChanged::class => [
            TrackApprovalMetrics::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}