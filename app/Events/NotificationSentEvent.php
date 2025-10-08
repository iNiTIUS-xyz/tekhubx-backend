<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSentEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $notification;

    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    // public function broadcastOn()
    // {
    //     return [
    //         new Channel('notification-channel.' . $this->notification->receiver_id),

    //     ];
    // }

    public function broadcastOn()
    {
        return [
            new Channel('notification-channel.' . $this->notification['receiver_id']), // Access receiver_id from the resource
        ];
    }

    public function broadcastWith()
    {
        return $this->notification->toArray(request()); // Ensure the resource is transformed to an array
    }

}
