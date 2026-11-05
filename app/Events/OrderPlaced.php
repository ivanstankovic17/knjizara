<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Nova porudžbina je sačuvana. Događaj se šalje tek posle potvrde
 * transakcije: ako se transakcija poništi, porudžbine nema, pa ni mejla.
 */
class OrderPlaced implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
