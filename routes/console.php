<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\PriceFormatter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('knjizara:cena {iznos}', function (PriceFormatter $prices, int $iznos) {
    $this->info($prices->format($iznos));
})->purpose('Prikazuje iznos u formatu knjižare');

// Dok menadžer ne dobije API za porudžbine (glava 12), status se menja ovde.
Artisan::command('knjizara:status {porudzbina} {status}', function (int $porudzbina, string $status) {
    $order = Order::findOrFail($porudzbina);
    $new = OrderStatus::tryFrom($status)
        ?? throw new InvalidArgumentException('Status mora biti: '
            .implode(', ', array_column(OrderStatus::cases(), 'value')));

    $order->changeStatus($new);
    $this->info("Porudžbina {$order->id}: {$order->status->value}");
})->purpose('Menja status porudžbine i obaveštava kupca');
