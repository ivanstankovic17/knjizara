<?php

namespace App\Exceptions;

use App\Models\Order;

class OrderNotCancellableException extends ConflictException
{
    public function __construct(public readonly Order $order)
    {
        parent::__construct(sprintf(
            'Porudžbina %d ima status "%s"; otkazati se može samo porudžbina na čekanju.',
            $order->id, $order->status->value,
        ));
    }

    public function type(): string
    {
        return 'porudzbina-ne-moze-da-se-otkaze';
    }

    public function title(): string
    {
        return 'Porudžbina ne može da se otkaže';
    }
}
