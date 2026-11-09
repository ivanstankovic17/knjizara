<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Porudžbinu vidi i otkazuje vlasnik ili menadžer. Za ostale ona
 * ne postoji: odgovor je isti 404 kao za nepostojeći broj (odeljak 12.5).
 */
class OrderPolicy
{
    public function view(User $user, Order $order): Response
    {
        return $this->ownerOrManager($user, $order);
    }

    public function cancel(User $user, Order $order): Response
    {
        return $this->ownerOrManager($user, $order);
    }

    /**
     * Za polje "can" u reprezentaciji: da li klijent treba da ponudi
     * dugme „Otkaži”. Ovde je i stanje porudžbine, a ne samo ko pita.
     */
    public function cancelNow(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::Pending
            && $this->cancel($user, $order)->allowed();
    }

    private function ownerOrManager(User $user, Order $order): Response
    {
        if ($order->user_id === $user->id
            || $user->hasPermission(Permission::ManageOrders)) {
            return Response::allow();
        }

        return Response::denyAsNotFound("Porudžbina {$order->id} ne postoji.");
    }
}
