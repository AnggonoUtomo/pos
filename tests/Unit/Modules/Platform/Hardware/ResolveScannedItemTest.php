<?php

namespace Tests\Unit\Modules\Platform\Hardware;

use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Items\Application\DTOs\SaleItemData;
use App\Modules\Inventory\Items\Application\DTOs\SaleScanData;
use App\Modules\Inventory\Items\Application\DTOs\SaleUnitData;
use App\Modules\Platform\Hardware\Application\Queries\ResolveScannedItem;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ResolveScannedItemTest extends TestCase
{
    public function test_public_catalog_is_projected_to_identity_only_and_preserves_zero(): void
    {
        $catalog = $this->createMock(SaleItemCatalog::class);
        $unit = new SaleUnitData('unit', 'UNIT', 'Unit', '1.000000', '0012345');
        $item = new SaleItemData('item', 'CCTV-DEMO', 'Kamera CCTV Demo', 'unit', 'Unit', [$unit], '/private/image');
        $catalog->expects($this->once())->method('scan')->with('0012345')->willReturn(new SaleScanData($item, 'unit'));

        $this->assertSame(['item_id' => 'item', 'sku' => 'CCTV-DEMO', 'name' => 'Kamera CCTV Demo',
            'unit_id' => 'unit', 'unit_name' => 'Unit', 'barcode' => '0012345'], (new ResolveScannedItem($catalog))->handle(' 0012345 '));
    }

    public function test_unknown_code_returns_no_identity(): void
    {
        $catalog = $this->createMock(SaleItemCatalog::class);
        $catalog->method('scan')->willReturn(null);
        $this->assertNull((new ResolveScannedItem($catalog))->handle('UNKNOWN'));
    }

    public function test_invalid_input_never_calls_catalog(): void
    {
        $catalog = $this->createMock(SaleItemCatalog::class);
        $catalog->expects($this->never())->method('scan');
        foreach (['', ' ', str_repeat('x', 65), "code\nvalue", 'kamera'.chr(233)] as $code) {
            try {
                (new ResolveScannedItem($catalog))->handle($code);
                $this->fail('Input invalid harus ditolak.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
