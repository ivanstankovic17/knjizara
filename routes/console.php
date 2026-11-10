<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\PriceFormatter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('knjizara:cena {iznos}', function (PriceFormatter $prices, int $iznos) {
    $this->info($prices->format($iznos));
})->purpose('Prikazuje iznos u formatu knjižare');

// Promena statusa iz konzole (glava 10). Od glave 12 menadžer to radi
// i preko API-ja: PATCH /api/v1/admin/orders/{id}.
Artisan::command('knjizara:status {porudzbina} {status}', function (int $porudzbina, string $status) {
    $order = Order::findOrFail($porudzbina);
    $new = OrderStatus::tryFrom($status)
        ?? throw new InvalidArgumentException('Status mora biti: '
            .implode(', ', array_column(OrderStatus::cases(), 'value')));

    $order->changeStatus($new);
    $this->info("Porudžbina {$order->id}: {$order->status->value}");
})->purpose('Menja status porudžbine i obaveštava kupca');

// Prvo pokretanje (README, odeljak 15.5): Reverb traži identifikator,
// ključ i tajnu aplikacije, a .env.example ih ima prazne, kao i APP_KEY.
Artisan::command('knjizara:reverb-keys', function () {
    $env = file_get_contents(base_path('.env'));
    $values = [
        'REVERB_APP_ID' => (string) random_int(100_000, 999_999),
        'REVERB_APP_KEY' => Str::lower(Str::random(20)),
        'REVERB_APP_SECRET' => Str::lower(Str::random(20)),
    ];

    foreach ($values as $name => $value) {
        // Postojeća vrednost se ne menja: klijenti već koriste taj ključ.
        $env = preg_replace("/^{$name}=$/m", "{$name}={$value}", $env, 1, $count);
        $this->line($count ? "{$name} je upisan." : "{$name} već ima vrednost.");
    }

    file_put_contents(base_path('.env'), $env);
})->purpose('Upisuje ključeve za Reverb u .env, ako su prazni');
