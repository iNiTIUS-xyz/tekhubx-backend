<?php

namespace App\Events;

use App\Models\LiveTracking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ProviderLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $location;
    public $provider;
    public $workOrder;
    /**
     * Create a new event instance.
     */
    public function __construct(LiveTracking $location)
    {
        $this->location = $location;
        $this->provider = $location->provider;
        $this->workOrder = $location->workOrder;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('tracking.' . $this->location->work_order_unique_id);
    }
     public function broadcastWith()
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'work_order_unique_id' => $this->workOrder->work_order_unique_id,
            'provider_id' => $this->provider->id,
            'provider_name' => $this->provider->name,
            'latitude' => $this->location->latitude,
            'longitude' => $this->location->longitude,
            'speed' => $this->location->speed,
            'heading' => $this->location->heading,
            'accuracy' => $this->location->accuracy,
            'status' => $this->location->status,
            'tracked_at' => $this->location->tracked_at->toIso8601String(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs()
    {
        return 'location.update';
    }
}
