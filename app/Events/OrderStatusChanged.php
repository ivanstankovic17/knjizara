<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Status porudžbine je promenjen. Ide samo vlasniku porudžbine, na
 * privatnom kanalu "korisnik.{id}" (dozvola u routes/channels.php).
 */
class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('korisnik.'.$this->order->user_id);
    }

    public function broadcastAs(): string
    {
        return 'porudzbina.status';
    }

    /**
     * @return array{id: int, status: string, total: int}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'status' => $this->order->status->value,
            'total' => $this->order->total,
        ];
    }
}
