<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\StockCountRepository;
use App\Modules\Inventory\Stock\Application\Contracts\WarehouseStockAccess;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class EnsureWarehouseStockWritable implements WarehouseStockAccess
{
    public function __construct(private StockCountRepository $counts, private WarehouseLookup $warehouses) {}

    public function assertWritable(string $warehouseId, ?string $stockCountId = null): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Guard gudang memerlukan transaction caller.');
        }
        $this->warehouses->findActive($warehouseId, lock: true) ?? throw new InvalidArgumentException('Gudang harus aktif.');
        $active = $this->counts->active($warehouseId);
        if ($active !== null && ! ($active['id'] === $stockCountId && $active['status'] === 'posting')) {
            throw new InvalidArgumentException('Gudang sedang opname '.$active['number'].'. Selesaikan atau batalkan sesi sebelum mutasi stok.');
        }
        if ($stockCountId !== null && ($active === null || $active['id'] !== $stockCountId || $active['status'] !== 'posting')) {
            throw new InvalidArgumentException('Konteks posting opname tidak valid.');
        }
    }
}
