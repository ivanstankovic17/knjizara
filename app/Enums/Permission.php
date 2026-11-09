<?php

namespace App\Enums;

/**
 * Dozvole: šta sme da se radi. Uloga je samo imenovan skup dozvola,
 * pa kod proverava dozvole, a ne uloge (odeljak 12.3).
 */
enum Permission: string
{
    case ManageCatalog = 'catalog.manage';
    case ManageOrders = 'orders.manage';
    case ViewReports = 'reports.view';
    case ManageUsers = 'users.manage';
}
