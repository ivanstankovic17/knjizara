<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Ko sme da sluša koji privatni kanal. Javni kanali, poput kanala
 * "katalog", nemaju pravilo, jer ih sme da sluša svako.
 */

// Obaveštenja o porudžbinama: samo vlasnik naloga (odeljak 10.7).
Broadcast::channel('korisnik.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
