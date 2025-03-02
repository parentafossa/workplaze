<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalActionPerformed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $instance;
    public $action;
    public $user;
    public $comments;
    public $submitType;

    public function __construct($instance, $action, $user, $comments = null, $submitType = null)
    {
        $this->instance = $instance;
        $this->action = $action;
        $this->user = $user;
        $this->comments = $comments;
        $this->submitType = $submitType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
