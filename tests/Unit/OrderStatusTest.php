<?php

use App\Enums\OrderStatus;

it('dozvoljava prelaz na čekanju → plaćena → poslata', function () {
    expect(OrderStatus::Pending->canBecome(OrderStatus::Paid))->toBeTrue()
        ->and(OrderStatus::Paid->canBecome(OrderStatus::Shipped))->toBeTrue();
});

it('odbija prelaz', function (OrderStatus $from, OrderStatus $to) {
    expect($from->canBecome($to))->toBeFalse();
})->with([
    'preskakanje plaćanja' => [OrderStatus::Pending, OrderStatus::Shipped],
    'unazad' => [OrderStatus::Shipped, OrderStatus::Paid],
    'iz otkazane' => [OrderStatus::Cancelled, OrderStatus::Paid],
    'otkazivanje kroz PATCH' => [OrderStatus::Pending, OrderStatus::Cancelled],
]);
