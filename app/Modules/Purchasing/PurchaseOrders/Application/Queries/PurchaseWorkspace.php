<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseOrderRepository;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SupplierLookup;

class PurchaseWorkspace
{
    public function __construct(private PurchaseOrderRepository $orders, private SupplierLookup $suppliers, private WarehouseLookup $warehouses, private ItemStockLookup $items, private UnitLookup $units) {}

    public function records(int $page, array $filters): array
    {
        return $this->orders->paginate($page, $filters);
    }

    public function options(): array
    {
        $units = $this->units->listActive();
        $activeIds = array_fill_keys(array_map(fn ($unit) => $unit->id, $units), true);
        $items = [];
        foreach ($this->items->choices() as $item) {
            if (! isset($activeIds[$item->baseUnitId])) {
                continue;
            }
            $choices = [];
            foreach ($item->units as $unit) {
                if ($unit->isPurchaseUnit && isset($activeIds[$unit->unitId])) {
                    $choices[] = ['unit_id' => $unit->unitId, 'conversion_ratio' => $unit->conversionRatio, 'barcode' => $unit->barcode];
                }
            }
            if ($choices !== []) {
                $items[] = ['id' => $item->id, 'sku' => $item->sku, 'name' => $item->name, 'base_unit_id' => $item->baseUnitId, 'units' => $choices];
            }
        }

        return ['suppliers' => $this->suppliers->choices(), 'warehouses' => $this->warehouses->choices(), 'units' => $units, 'items' => $items];
    }
}
