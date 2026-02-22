<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSentEvent implements ShouldBroadcastNow
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
        $workOrderId = $this->message->work_order_unique_id;
        $receiverId = $this->message->receiver_id;
        $senderId = $this->message->sender_id;

        return [
            // New scoped channels (frontend currently subscribes to this pattern)
            new Channel('chat-channel.' . $workOrderId . '.' . $receiverId . '.' . $senderId),
            new Channel('chat-channel.' . $workOrderId . '.' . $senderId . '.' . $receiverId),
            // Legacy channels (kept for backward compatibility)
            new Channel('chat-channel.' . $receiverId . '.' . $senderId),
            new Channel('chat-channel.' . $senderId . '.' . $receiverId),
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
                    'id' => $this->message?->sender?->profile?->id,
                    'user_id' => $this->message?->sender?->profile?->user_id,
                    'first_name' => $this->message?->sender?->profile?->first_name,
                    'last_name' => $this->message?->sender?->profile?->last_name,
                    'profile_image' => $this->message?->sender?->profile?->profile_image,
                ]
            ],
            'receiver' => [
                'id' => $this->message?->receiver?->id,
                'username' => $this->message?->receiver?->username,
                'profile' => [
                    'id' => $this->message?->receiver?->profile?->id,
                    'user_id' => $this->message?->receiver?->profile?->user_id,
                    'first_name' => $this->message?->receiver?->profile?->first_name,
                    'last_name' => $this->message?->receiver?->profile?->last_name,
                    'profile_image' => $this->message?->receiver?->profile?->profile_image,
                ]
            ],
            'message' => $this->message?->message,
            'request_date_time' => $this->message?->request_date_time,
            'created_at' => $this->message?->created_at,
        ];
    }
}
