<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSentEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return [
            new Channel('chat-channel.' . $this->message->receiver_id . '.' . $this->message->sender_id),
            new Channel('chat-channel.' . $this->message->sender_id . '.' . $this->message->receiver_id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'work_order_unique_id' => $this->message?->work_order_unique_id,
            'receiver_id' => $this->message->receiver_id,
            'sender_id' => $this->message->sender_id,
            'sender' => [
                'id' => $this->message?->sender?->id,
                'username' => $this->message?->sender?->username,
                'profile' => [
                    'id' => $this->message?->sender?->profile?->last_name,
                    'user_id' => $this->message?->sender?->profile?->last_name,
                    'first_name' => $this->message?->sender?->profile?->first_name,
                    'last_name' => $this->message?->sender?->profile?->last_name,
                    'profile_image' => $this->message?->sender?->profile?->profile_image,
                ]
            ],
            'receiver' => [
                'id' => $this->message?->receiver?->id,
                'username' => $this->message?->receiver?->username,
                'profile' => [
                    'id' => $this->message?->sender?->profile?->last_name,
                    'user_id' => $this->message?->sender?->profile?->last_name,
                    'first_name' => $this->message?->sender?->profile?->first_name,
                    'last_name' => $this->message?->sender?->profile?->last_name,
                    'profile_image' => $this->message?->sender?->profile?->profile_image,
                ]
            ],
            'message' => $this->message?->message,
            'request_date_time' => $this->message?->request_date_time,
            'created_at' => $this->message?->created_at,
        ];
    }
}
