<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\User;
use App\Services\PriceFormatter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
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

// Zakazani poslovi (odeljak 16.5). Na produkciji ih pokreće kontejner
// scheduler (php artisan schedule:work), a spisak daje schedule:list.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('queue:prune-failed --hours=168')->daily();

// Prvi administrator na produkciji (odeljak 16.5), gde nema fabrika ni
// probnih naloga. Lozinka je slučajna i prikazuje se samo jednom.
Artisan::command('knjizara:admin {email} {name}', function (
    string $email,
    string $name,
) {
    $password = Str::password(20);

    $user = User::create([
        'name' => $name, 'email' => $email, 'password' => $password,
    ]);
    $user->forceFill([
        'role' => Role::Admin, 'email_verified_at' => now(),
    ])->save();

    $this->info("Administrator {$email} je napravljen.");
    $this->line("Privremena lozinka: {$password}");
    $this->line('Promenite je posle prve prijave.');
})->purpose('Pravi nalog administratora sa slučajnom lozinkom');
