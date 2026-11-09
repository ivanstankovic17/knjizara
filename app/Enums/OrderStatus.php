<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    /**
     * Dozvoljeni prelazi kroz PATCH (glava 12): na čekanju → plaćena →
     * poslata. Otkazivanje ide posebnom akcijom, jer vraća knjige na stanje.
     */
    public function canBecome(self $next): bool
    {
        return match ($this) {
            self::Pending => $next === self::Paid,
            self::Paid => $next === self::Shipped,
            default => false,
        };
    }
}
