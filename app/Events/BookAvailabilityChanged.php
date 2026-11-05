<?php

namespace App\Events;

use App\Models\Book;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Knjiga je rasprodata ili je ponovo na stanju. Ide svima koji gledaju
 * katalog, na javnom kanalu "katalog".
 */
class BookAvailabilityChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Book $book) {}

    public function broadcastOn(): Channel
    {
        return new Channel('katalog');
    }

    public function broadcastAs(): string
    {
        return 'knjiga.dostupnost';
    }

    /**
     * Kupci vide samo da li knjige ima, a ne koliko (odeljak 6.2).
     *
     * @return array{id: int, in_stock: bool}
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->book->id, 'in_stock' => $this->book->stock > 0];
    }
}
