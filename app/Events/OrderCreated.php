<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public User $user
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('backoffice'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'total' => (float) $this->order->total,
            'status' => $this->order->status,
            'site_id' => $this->order->site_id,
        ];
    }
}
