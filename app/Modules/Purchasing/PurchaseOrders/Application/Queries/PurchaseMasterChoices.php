<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SupplierLookup;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use Illuminate\Validation\ValidationException;

final class PurchaseMasterChoices
{
    public function __construct(private ItemStockLookup $items, private SupplierLookup $suppliers,
        private UnitLookup $units, private ItemPriceCatalog $prices) {}

    public function creationOptions(): array
    {
        return ['units' => $this->units->listActive(), 'levels' => $this->prices->activeLevels()];
    }

    public function find(string $kind, string $id): array
    {
        if ($kind === 'supplier') {
            $supplier = $this->suppliers->findActive(strtolower($id)) ?? throw ValidationException::withMessages(['master' => 'Supplier harus aktif.']);

            return ['id' => $supplier->id, 'code' => $supplier->code, 'name' => $supplier->name];
        }
        $item = $this->items->findActive(strtolower($id)) ?? throw ValidationException::withMessages(['master' => 'Barang harus aktif.']);
        $active = $this->units->findManyActive(array_unique([$item->baseUnitId, ...array_map(fn ($unit) => $unit->unitId, $item->units)]));
        if (! isset($active[$item->baseUnitId])) {
            throw ValidationException::withMessages(['master' => 'Satuan dasar harus aktif.']);
        }
        $units = [];
        foreach ($item->units as $unit) {
            if ($unit->isPurchaseUnit && isset($active[$unit->unitId])) {
                $units[] = ['unit_id' => $unit->unitId, 'conversion_ratio' => $unit->conversionRatio, 'barcode' => $unit->barcode];
            }
        }
        if ($units === []) {
            throw ValidationException::withMessages(['master' => 'Barang belum memiliki satuan beli aktif.']);
        }

        return ['id' => $item->id, 'sku' => $item->sku, 'name' => $item->name, 'base_unit_id' => $item->baseUnitId, 'units' => $units];
    }
}
