<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Domain\ValueObjects\OpeningValuation;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class PrepareOpening
{
    public function __construct(private WarehouseLookup $warehouses, private ItemStockLookup $items, private UnitLookup $units) {}

    public static function rules(): array
    {
        return ['warehouse_id' => ['required', 'ulid'], 'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*.item_id' => ['required', 'ulid', 'distinct'], 'lines.*.unit_id' => ['required', 'ulid'],
            'lines.*.quantity' => ['required', 'string', 'max:21'], 'lines.*.unit_cost' => ['required', 'string', 'max:21']];
    }

    /** Must run inside the shared transaction. */
    public function handle(array $input): array
    {
        if (is_string($input['warehouse_id'] ?? null)) {
            $input['warehouse_id'] = strtolower($input['warehouse_id']);
        }
        if (is_array($input['lines'] ?? null)) {
            foreach ($input['lines'] as &$row) {
                if (is_array($row)) {
                    foreach (['item_id', 'unit_id'] as $key) {
                        if (is_string($row[$key] ?? null)) {
                            $row[$key] = strtolower($row[$key]);
                        }
                    }
                }
            }
            unset($row);
        }
        $input = Validator::make($input, self::rules())->validate();
        $warehouse = $this->warehouses->findActive($input['warehouse_id'], lock: true);
        if ($warehouse === null) {
            throw new InvalidArgumentException('Gudang harus aktif dan tersedia.');
        }
        $unitIds = array_column($input['lines'], 'unit_id');
        // Discovery is non-locking; base_unit_id is immutable. Actual item data is re-read under lock.
        foreach ($input['lines'] as $row) {
            $item = $this->items->findActive($row['item_id'])
                ?? throw new InvalidArgumentException('Barang harus aktif dan tersedia.');
            $unitIds[] = $item->baseUnitId;
        }
        $unitIds = array_unique($unitIds);
        sort($unitIds);
        $units = [];
        foreach ($unitIds as $id) {
            $units[$id] = $this->units->findActiveById($id, lock: true)
                ?? throw new InvalidArgumentException('Satuan harus aktif dan tersedia.');
        }
        $rows = $input['lines'];
        usort($rows, fn ($a, $b) => strcmp($a['item_id'], $b['item_id']));
        $lines = [];
        foreach ($rows as $row) {
            $item = $this->items->findActive($row['item_id'], lock: true)
                ?? throw new InvalidArgumentException('Barang harus aktif dan tersedia.');
            $selected = null;
            foreach ($item->units as $unit) {
                if ($unit->unitId === $row['unit_id']) {
                    $selected = $unit;
                }
            }
            if ($selected === null) {
                throw new InvalidArgumentException('Satuan tidak terdaftar pada barang.');
            }
            $base = $this->units->findActiveById($item->baseUnitId)
                ?? throw new InvalidArgumentException('Satuan dasar harus aktif.');
            $value = OpeningValuation::fromStrings($row['quantity'], $selected->conversionRatio, $row['unit_cost']);
            $lines[] = ['item_id' => $item->id, 'unit_id' => $selected->unitId, 'base_unit_id' => $item->baseUnitId,
                'sku' => $item->sku, 'item_name' => $item->name, 'unit_name' => $units[$selected->unitId]->name,
                'base_unit_name' => $base->name, 'quantity' => $value->quantity, 'conversion_ratio' => $value->ratio,
                'unit_cost' => $value->unitCost, 'base_quantity' => $value->baseQuantity, 'total_value' => $value->totalValue];
        }

        return [['warehouse_id' => $warehouse->id, 'warehouse_code' => $warehouse->code, 'warehouse_name' => $warehouse->name,
            'notes' => isset($input['notes']) ? trim($input['notes']) : null], $lines];
    }
}
