<?php

namespace Tests\Feature\Modules\Sales\POS;

use App\Modules\Finance\Payments\Application\Contracts\SalePaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\SalePaymentRequest;
use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Stock\Application\Contracts\SaleStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\StockAvailability;
use App\Modules\Inventory\Stock\Application\DTOs\SaleStockRequest;
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
            fn () => app(SaleStockWriter::class)->issue(new SaleStockRequest('', '', '', [], 1)),
            fn () => app(SalePaymentWriter::class)->receive(new SalePaymentRequest('', '', '', [], 1)),
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
