<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockRepository;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

final class ManageMinimumStock
{
    public function __construct(private MinimumStockRepository $records, private WarehouseLookup $warehouses,
        private ItemStockLookup $items, private UnitLookup $units) {}

    public function create(array $input, int $actorId): string
    {
        $data = Validator::make($input, ['warehouse_id' => ['required', 'ulid'], 'item_id' => ['required', 'ulid']] + $this->rules())->validate();

        return DB::transaction(function () use ($data, $actorId): string {
            $warehouse = strtolower($data['warehouse_id']);
            $itemId = strtolower($data['item_id']);
            $base = $this->masters($warehouse, $itemId);
            if ($this->records->pairExists($warehouse, $itemId)) {
                throw new InvalidArgumentException('Pasangan sudah diatur, termasuk data terhapus. Edit atau pulihkan pengaturan lama.');
            }

            return $this->records->insert(['warehouse_id' => $warehouse, 'item_id' => $itemId, 'base_unit_id' => $base,
                'minimum_quantity' => (string) BigDecimal::of($data['minimum_quantity'])->toScale(6), 'is_active' => (bool) $data['is_active']], $actorId);
        }, 3);
    }

    public function update(string $id, array $input, int $actorId): void
    {
        $data = Validator::make($input, $this->rules() + ['version' => ['required', 'integer', 'min:0', 'max:2147483646']])->validate();
        DB::transaction(function () use ($id, $data, $actorId): void {
            $record = $this->locked($id, (int) $data['version']);
            if ($record['deleted_at'] !== null) {
                throw new InvalidArgumentException('Pulihkan pengaturan sebelum mengedit.');
            }
            $minimum = (string) BigDecimal::of($data['minimum_quantity'])->toScale(6);
            if (BigDecimal::of($record['minimum_quantity'])->isEqualTo($minimum) && $record['is_active'] === (bool) $data['is_active']) {
                return;
            }
            $this->records->change($id, ['minimum_quantity' => $minimum, 'is_active' => (bool) $data['is_active'], 'version' => $record['version'] + 1], 'updated', $actorId);
        }, 3);
    }

    public function remove(string $id, int $version, bool $restore, int $actorId): void
    {
        DB::transaction(function () use ($id, $version, $restore, $actorId): void {
            $record = $this->locked($id, $version, $restore);
            if (($record['deleted_at'] !== null) !== $restore) {
                throw new InvalidArgumentException('Status pengaturan berubah. Muat ulang.');
            }
            $this->records->change($id, ['deleted_at' => $restore ? null : now(), 'version' => $version + 1], $restore ? 'restored' : 'deleted', $actorId);
        }, 3);
    }

    private function locked(string $id, int $version, bool $requireActive = true): array
    {
        $initial = $this->records->find($id);
        if ($requireActive) {
            $this->masters($initial['warehouse_id'], $initial['item_id']);
        }
        $record = $this->records->find($id, true);
        if ($record['version'] !== $version) {
            throw new InvalidArgumentException('Pengaturan berubah di tab lain. Muat ulang.');
        }

        return $record;
    }

    private function masters(string $warehouseId, string $itemId): string
    {
        $this->warehouses->findActive($warehouseId, lock: true) ?? throw new InvalidArgumentException('Gudang harus aktif.');
        $item = $this->items->findActive($itemId) ?? throw new InvalidArgumentException('Barang harus aktif.');
        $this->units->findActiveById($item->baseUnitId, lock: true) ?? throw new InvalidArgumentException('Satuan dasar harus aktif.');
        $current = $this->items->findActive($itemId, lock: true) ?? throw new InvalidArgumentException('Barang harus aktif.');
        if ($current->baseUnitId !== $item->baseUnitId) {
            throw new InvalidArgumentException('Satuan dasar berubah. Muat ulang.');
        }

        return $current->baseUnitId;
    }

    private function rules(): array
    {
        return ['minimum_quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'], 'is_active' => ['required', 'boolean']];
    }
}
