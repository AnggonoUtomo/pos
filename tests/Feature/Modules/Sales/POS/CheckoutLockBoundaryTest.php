<?php

namespace Tests\Feature\Modules\Sales\POS;

use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Stock\Application\Contracts\StockAvailability;
use App\Modules\Sales\Parties\Application\Contracts\CustomerSalesLookup;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CheckoutLockBoundaryTest extends TestCase
{
    use DatabaseMigrations;

    public function test_published_checkout_locks_require_default_transaction(): void
    {
        foreach ([fn () => app(SaleItemCatalog::class)->findMany([], lock: true),
            fn () => app(CustomerSalesLookup::class)->findActive('missing', lock: true),
            fn () => app(ItemPriceCatalog::class)->forItems([], lock: true),
            fn () => app(StockAvailability::class)->forItems('missing', [], lock: true)] as $call) {
            $failed = false;
            try {
                $call();
            } catch (\LogicException) {
                $failed = true;
            }
            $this->assertTrue($failed);
        }
    }
}
