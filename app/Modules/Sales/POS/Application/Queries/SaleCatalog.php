<?php

namespace App\Modules\Sales\POS\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Stock\Application\Contracts\StockAvailability;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use Illuminate\Support\Facades\DB;

class SaleCatalog
{
    public function __construct(private SaleContext $context, private SaleItemCatalog $items,
        private ItemPriceCatalog $prices, private StockAvailability $stock) {}

    public function handle(string $warehouseId, string $customerId, string $search, int $page): array
    {
        return DB::transaction(function () use ($warehouseId, $customerId, $search, $page): array {
            $context = $this->context->resolve($warehouseId, $customerId);
            $rows = $this->items->search($search, $page);
            $ids = array_map(fn ($item) => $item->id, $rows['data']);
            $prices = $this->prices->forItems($ids, $context['customer']->levelId);
            $stock = $this->stock->forItems($warehouseId, $ids);
            $result = [];
            foreach ($rows['data'] as $item) {
                $amounts = array_column($prices[$item->id] ?? [], 'amount', 'unitId');
                $result[] = ['id' => $item->id, 'sku' => $item->sku, 'name' => $item->name,
                    'base_unit_id' => $item->baseUnitId, 'base_unit_name' => $item->baseUnitName, 'stock' => $stock[$item->id],
                    'units' => array_map(fn ($unit) => ['id' => $unit->id, 'code' => $unit->code, 'name' => $unit->name,
                        'ratio' => $unit->ratio, 'barcode' => $unit->barcode, 'price' => $amounts[$unit->id] ?? null], $item->units)];
            }

            return ['items' => $result, 'has_more' => $rows['has_more'], 'customer' => $context['customer']];
        });
    }
}
