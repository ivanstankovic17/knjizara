<?php

namespace App\Enums;

enum Role: string
{
    case Customer = 'customer';
    case Manager = 'manager';
    case Admin = 'admin';

    /**
     * Dozvole uloge. Kupac nema nijednu: sve što kupac sme (svoje
     * porudžbine) zavisi od vlasništva, a ne od uloge.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Customer => [],
            self::Manager => [
                Permission::ManageCatalog,
                Permission::ManageOrders,
                Permission::ViewReports,
            ],
            self::Admin => Permission::cases(),
        };
    }
}
