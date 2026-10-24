<?php

namespace App\Modules\Inventory\Transfers\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Transfers\Domain\ValueObjects\TransferQuantity;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class PrepareTransferDraft
{
    public function __construct(private WarehouseLookup $warehouses, private ItemStockLookup $items, private UnitLookup $units) {}

    public static function rules(): array
    {
        return ['source_warehouse_id' => ['required', 'ulid'], 'destination_warehouse_id' => ['required', 'ulid', 'different:source_warehouse_id'],
            'notes' => ['nullable', 'string', 'max:500'], 'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*' => ['required', 'array:item_id,unit_id,quantity'], 'lines.*.item_id' => ['required', 'ulid', 'distinct'],
            'lines.*.unit_id' => ['required', 'ulid'], 'lines.*.quantity' => ['required', 'string', 'max:21']];
    }

    public static function normalize(array $input): array
    {
        foreach (['source_warehouse_id', 'destination_warehouse_id'] as $key) {
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
        $data = Validator::make(self::normalize($input), self::rules())->validate();
        $ids = [$data['source_warehouse_id'], $data['destination_warehouse_id']];
        sort($ids);
        $warehouses = [];
        foreach ($ids as $id) {
            $warehouses[$id] = $this->warehouses->findActive($id, lock: true)
                ?? throw ValidationException::withMessages(['source_warehouse_id' => 'Kedua gudang harus aktif dan tersedia.']);
        }
        $unitIds = array_column($data['lines'], 'unit_id');
        foreach ($data['lines'] as $line) {
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
        $rows = $data['lines'];
        usort($rows, fn ($a, $b) => strcmp($a['item_id'], $b['item_id']));
        $lines = [];
        foreach ($rows as $row) {
            $item = $this->items->findActive($row['item_id'], lock: true)
                ?? throw ValidationException::withMessages(['lines' => 'Barang harus aktif dan tersedia.']);
            $unit = collect($item->units)->first(fn ($unit) => $unit->unitId === $row['unit_id']);
            if ($unit === null || ! isset($units[$item->baseUnitId])) {
                throw ValidationException::withMessages(['lines' => 'Satuan harus terdaftar pada barang yang dipilih.']);
            }
            try {
                $value = TransferQuantity::fromStrings($row['quantity'], $unit->conversionRatio);
            } catch (InvalidArgumentException $error) {
                throw ValidationException::withMessages(['lines' => $error->getMessage()]);
            }
            $lines[] = ['item_id' => $item->id, 'unit_id' => $unit->unitId, 'base_unit_id' => $item->baseUnitId,
                'sku' => $item->sku, 'item_name' => $item->name, 'unit_name' => $units[$unit->unitId]->name,
                'base_unit_name' => $units[$item->baseUnitId]->name, 'quantity' => $value->quantity,
                'conversion_ratio' => $value->ratio, 'base_quantity' => $value->baseQuantity];
        }
        $header = ['notes' => isset($data['notes']) && trim($data['notes']) !== '' ? trim($data['notes']) : null];
        foreach (['source', 'destination'] as $prefix) {
            $warehouse = $warehouses[$data[$prefix.'_warehouse_id']];
            foreach (['id', 'code', 'name'] as $key) {
                $header[$prefix.'_warehouse_'.$key] = $warehouse->$key;
            }
        }

        return [$header, $lines];
    }
}
