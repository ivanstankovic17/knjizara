<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Mejl sa potvrdom porudžbine. Slanje mejla je sporo i zavisi od
 * spoljnog servera, pa ide u red poslova, a ne u zahtev (odeljak 10.5).
 */
class SendOrderConfirmation implements ShouldQueue
{
    /** Broj pokušaja i pauze između njih, u sekundama. */
    public int $tries = 3;

    public array $backoff = [10, 60];

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load('user', 'items.book');

        Mail::to($order->user)->send(new OrderConfirmation($order));
    }
}
