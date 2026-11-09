<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
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

    /**
     * Ko gleda resurs. Na javnim rutama nema middleware-a auth:sanctum,
     * pa se guard sanctum pita direktno: on prepoznaje i sesiju i token.
     */
    protected function viewer(Request $request): ?User
    {
        return $request->user('sanctum');
    }
}
