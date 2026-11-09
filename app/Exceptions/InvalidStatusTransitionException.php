<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use App\Models\Order;

class InvalidStatusTransitionException extends ConflictException
{
    public function __construct(public readonly Order $order, OrderStatus $next)
    {
        parent::__construct(sprintf(
            'Porudžbina %d ima status "%s" i ne može da pređe u "%s".',
            $order->id, $order->status->value, $next->value,
        ));
    }

    public function type(): string
    {
        return 'nedozvoljena-promena-statusa';
    }

    public function title(): string
    {
        return 'Nedozvoljena promena statusa';
    }
}
