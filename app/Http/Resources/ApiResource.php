<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Zajednička osnova svih resursa API-ja knjižare.
 */
abstract class ApiResource extends JsonResource
{
    /**
     * Ćirilica, latinica sa kvačicama i kose crte u adresama ostaju
     * čitljive, umesto ć i \/.
     */
    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
