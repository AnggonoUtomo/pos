<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface MinimumStockAlerts
{
    /** Counts configured, active and eligible item/warehouse pairs; no stock or payment writes. */
    public function summary(): array;
}
