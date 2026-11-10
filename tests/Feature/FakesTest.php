<?php

use App\Actions\CancelOrder;
use App\Actions\PlaceOrder;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Jobs\GenerateSalesReport;
use App\Listeners\SendOrderConfirmation;
use App\Mail\OrderConfirmation;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

it('posle porudžbine šalje događaj OrderPlaced', function () {
    Event::fake([OrderPlaced::class]);
    $book = Book::factory()->create(['stock' => 3]);

    $order = app(PlaceOrder::class)
        ->handle(User::factory()->create(), [$book->id => 1]);

    Event::assertDispatched(OrderPlaced::class,
        fn (OrderPlaced $e) => $e->order->is($order));
    Event::assertListening(OrderPlaced::class, SendOrderConfirmation::class);
});

it('listener šalje kupcu mejl sa stavkama i ukupnim iznosom', function () {
    Mail::fake();
    $kupac = User::factory()->create(['name' => 'Petar Ilić']);
    $book = Book::factory()->create(
        ['title' => 'Most na Nišavi', 'price' => 1490, 'stock' => 3]);

    $order = app(PlaceOrder::class)->handle($kupac, [$book->id => 2]);

    $naslov = "Knjižara: porudžbina {$order->id} je primljena";
    Mail::assertSent(OrderConfirmation::class,
        fn (OrderConfirmation $mail) => $mail->hasTo($kupac->email)
            && $mail->hasSubject($naslov));
    Mail::assertSentCount(1);

    (new OrderConfirmation($order->load('user', 'items.book')))
        ->assertSeeInText('Poštovani/a Petar Ilić')
        ->assertSeeInText('Most na Nišavi × 2 = 2.980 RSD')
        ->assertSeeInText('Ukupno: 2.980 RSD');
});

it('listener ide u red poslova sa tri pokušaja', function () {
    Queue::fake();
    $book = Book::factory()->create(['stock' => 3]);

    app(PlaceOrder::class)->handle(User::factory()->create(), [$book->id => 1]);

    Queue::assertPushed(CallQueuedListener::class,
        fn ($job) => $job->class === SendOrderConfirmation::class
            && $job->tries === 3);
});

it('izveštaj se samo stavlja u red, a odgovor je 202', function () {
    Queue::fake();
    Sanctum::actingAs(User::factory()->manager()->create());

    $response = $this->postJson('/api/v1/reports/sales')
        ->assertAccepted()
        ->assertJsonPath('data.status', 'pending');

    $id = $response->json('data.id');
    Queue::assertPushed(GenerateSalesReport::class,
        fn (GenerateSalesReport $job) => $job->report->id === $id);

    $this->getJson($response->headers->get('Location'))
        ->assertOk()
        ->assertHeader('Retry-After', '2');
});

it('posao izveštaja sabira prodaju po kategorijama, bez otkazanih', function () {
    $roman = Category::factory()->create(['name' => 'Roman']);
    $poezija = Category::factory()->create(['name' => 'Poezija']);
    $a = Book::factory()->for($roman)->create(['price' => 1000, 'stock' => 10]);
    $b = Book::factory()->for($poezija)->create(['price' => 500, 'stock' => 10]);
    $kupac = User::factory()->create();

    app(PlaceOrder::class)->handle($kupac, [$a->id => 2, $b->id => 1]);
    $otkazana = app(PlaceOrder::class)->handle($kupac, [$a->id => 5]);
    app(CancelOrder::class)->handle($otkazana);

    $report = Report::create(['type' => 'sales']);
    (new GenerateSalesReport($report))->handle();

    expect($report->fresh())
        ->status->toBe('done')
        ->result->total->toBe(2500)
        ->result->categories->{0}->category->toBe('Roman')
        ->result->categories->{0}->copies->toBe(2);
});

it('promena statusa šalje događaj samo vlasniku porudžbine', function () {
    Event::fake([OrderStatusChanged::class]);
    Sanctum::actingAs(User::factory()->manager()->create());
    $order = Order::factory()->create();

    $this->patchJson("/api/v1/admin/orders/{$order->id}", ['status' => 'paid'])
        ->assertOk();

    $kanal = "private-korisnik.{$order->user_id}";
    Event::assertDispatched(OrderStatusChanged::class,
        fn (OrderStatusChanged $e) => $e->broadcastOn()->name === $kanal
            && $e->broadcastWith()['status'] === 'paid');
});

it('registracija šalje mejl za potvrdu adrese', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jelena', 'email' => 'jelena@primer.test',
        'password' => 'tajna12345', 'password_confirmation' => 'tajna12345',
    ])->assertCreated();

    $jelena = User::where('email', 'jelena@primer.test')->sole();
    Notification::assertSentTo($jelena, VerifyEmail::class);
    expect($jelena->hasVerifiedEmail())->toBeFalse();
});
