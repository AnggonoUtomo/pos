<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Purchasing\PurchaseOrders\Domain\ValueObjects\PurchaseLineValue;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SupplierLookup;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PreparePurchaseOrder
{
    public function __construct(private WarehouseLookup $warehouses, private SupplierLookup $suppliers, private ItemStockLookup $items, private UnitLookup $units) {}

    public static function rules(): array
    {
        return ['supplier_id' => ['required', 'ulid'], 'warehouse_id' => ['required', 'ulid'],
            'notes' => ['nullable', 'string', 'max:500'], 'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*.item_id' => ['required', 'ulid', 'distinct'], 'lines.*.unit_id' => ['required', 'ulid'],
            'lines.*.quantity' => ['required', 'string', 'max:21'], 'lines.*.unit_cost' => ['required', 'string', 'max:21']];
    }

    public static function normalize(array $input): array
    {
        foreach (['supplier_id', 'warehouse_id'] as $key) {
            if (is_string($input[$key] ?? null)) {
                $input[$key] = strtolower($input[$key]);
            }
        }
        if (is_array($input['lines'] ?? null)) {
            foreach ($input['lines'] as &$line) {
                if (! is_array($line)) {
                    continue;
                }
                foreach (['item_id', 'unit_id'] as $key) {
                    if (is_string($line[$key] ?? null)) {
                        $line[$key] = strtolower($line[$key]);
                    }
                }
            }
            unset($line);
        }

        return $input;
    }

    public function handle(array $input): array
    {
        $input = Validator::make(self::normalize($input), self::rules())->validate();
        $warehouse = $this->warehouses->findActive($input['warehouse_id'], lock: true)
            ?? throw ValidationException::withMessages(['warehouse_id' => 'Gudang harus aktif dan tersedia.']);
        $supplier = $this->suppliers->findActive($input['supplier_id'], lock: true)
            ?? throw ValidationException::withMessages(['supplier_id' => 'Supplier harus aktif dan tersedia.']);
        $unitIds = array_column($input['lines'], 'unit_id');
        foreach ($input['lines'] as $line) {
            $item = $this->items->findActive($line['item_id'])
                ?? throw ValidationException::withMessages(['lines' => 'Barang harus aktif dan tersedia.']);
            $unitIds[] = $item->baseUnitId;
        }
        $unitIds = array_unique($unitIds);
        sort($unitIds);
        $units = [];
        foreach ($unitIds as $id) {
            $units[$id] = $this->units->findActiveById($id, lock: true)
                ?? throw ValidationException::withMessages(['lines' => 'Satuan dan satuan dasar harus aktif.']);
        }
        $rows = $input['lines'];
        usort($rows, fn ($a, $b) => strcmp($a['item_id'], $b['item_id']));
        $lines = [];
        $total = BigDecimal::zero()->toScale(6);
        foreach ($rows as $row) {
            $item = $this->items->findActive($row['item_id'], lock: true)
                ?? throw ValidationException::withMessages(['lines' => 'Barang harus aktif dan tersedia.']);
            $selected = null;
            foreach ($item->units as $unit) {
                if ($unit->unitId === $row['unit_id'] && $unit->isPurchaseUnit) {
                    $selected = $unit;
                }
            }
            if ($selected === null) {
                throw ValidationException::withMessages(['lines' => 'Satuan harus terdaftar sebagai satuan pembelian barang.']);
            }
            try {
                $value = PurchaseLineValue::fromStrings($row['quantity'], $selected->conversionRatio, $row['unit_cost']);
            } catch (InvalidArgumentException $error) {
                throw ValidationException::withMessages(['lines' => $error->getMessage()]);
            }
            $total = $total->plus($value->totalValue);
            $lines[] = ['item_id' => $item->id, 'unit_id' => $selected->unitId, 'base_unit_id' => $item->baseUnitId,
                'sku' => $item->sku, 'item_name' => $item->name, 'unit_name' => $units[$selected->unitId]->name,
                'base_unit_name' => $units[$item->baseUnitId]->name, 'quantity' => $value->quantity,
                'conversion_ratio' => $value->ratio, 'unit_cost' => $value->unitCost,
                'base_quantity' => $value->baseQuantity, 'total_value' => $value->totalValue];
        }
        if ($total->isGreaterThan('999999999999999999999999.999999')) {
            throw ValidationException::withMessages(['lines' => 'Total pembelian melebihi kapasitas.']);
        }

        return [['supplier_id' => $supplier->id, 'supplier_code' => $supplier->code, 'supplier_name' => $supplier->name,
            'warehouse_id' => $warehouse->id, 'warehouse_code' => $warehouse->code, 'warehouse_name' => $warehouse->name,
            'notes' => isset($input['notes']) && trim($input['notes']) !== '' ? trim($input['notes']) : null, 'total_value' => (string) $total], $lines];
    }
}
