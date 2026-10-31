<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Contracts\StockCountRepository;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ManageStockCount
{
    public function __construct(private StockCountRepository $counts, private WarehouseLookup $warehouses,
        private ItemStockLookup $items, private UnitLookup $units) {}

    public function start(string $warehouseId, string $notes, int $actorId, ?string $key = null): string
    {
        $data = Validator::make(['warehouse_id' => $warehouseId, 'notes' => trim($notes), 'key' => $key ?? (string) Str::ulid()], [
            'warehouse_id' => ['required', 'ulid'], 'notes' => ['required', 'string', 'max:500'], 'key' => ['required', 'ulid']])->validate();

        return DB::transaction(function () use ($data, $actorId): string {
            $warehouse = $this->warehouses->findActive(strtolower($data['warehouse_id']), lock: true)
                ?? throw new InvalidArgumentException('Gudang harus aktif.');
            $id = strtolower($data['key']);
            try {
                $existing = $this->counts->find($id, true);
                if ($existing['warehouse_id'] !== $warehouse->id || $existing['notes'] !== $data['notes'] || $existing['created_by'] !== $actorId) {
                    throw new InvalidArgumentException('Kunci opname sudah digunakan dengan isi berbeda.');
                }

                return $id;
            } catch (ModelNotFoundException) {
                // A fresh key starts a new session under the same warehouse row lock as writers.
            }
            if ($this->counts->active($warehouse->id) !== null) {
                throw new InvalidArgumentException('Gudang sudah memiliki sesi opname aktif.');
            }
            $this->counts->create(['id' => $id, 'number' => 'SO-'.strtoupper($id), 'warehouse_id' => $warehouse->id,
                'active_warehouse_id' => $warehouse->id, 'warehouse_name' => $warehouse->name, 'status' => 'counting',
                'notes' => $data['notes'], 'version' => 0, 'created_by' => $actorId]);
            foreach ($this->counts->balances($warehouse->id) as $balance) {
                $this->insertLine($id, $balance['item_id'], (string) $balance['quantity']);
            }
            $this->counts->audit($id, 'started', $actorId);

            return $id;
        }, 3);
    }

    public function cancel(string $id, int $version, string $reason, int $actorId): void
    {
        $this->transition($id, $version, 'cancelled', $reason, $actorId);
    }

    public function submit(string $id, int $version, int $actorId): void
    {
        $this->transition($id, $version, 'review', '', $actorId);
    }

    public function reopen(string $id, int $version, string $reason, int $actorId): void
    {
        $this->transition($id, $version, 'counting', $reason, $actorId);
    }

    private function transition(string $id, int $version, string $status, string $reason, int $actorId): void
    {
        DB::transaction(function () use ($id, $version, $status, $reason, $actorId): void {
            $record = $this->lock($id, $version);
            $allowed = $status === 'review' ? ['counting'] : ($status === 'counting' ? ['review'] : ['counting', 'review']);
            if (! in_array($record['status'], $allowed, true)) {
                throw new InvalidArgumentException('Status opname tidak sesuai. Muat ulang sesi.');
            }
            if ($status === 'review' && ($record['lines'] === [] || collect($record['lines'])->contains(fn ($line) => $line['counted_quantity'] === null))) {
                throw new InvalidArgumentException('Seluruh barang wajib dihitung; isi 0 bila fisik kosong.');
            }
            if ($status !== 'review') {
                Validator::make(['reason' => trim($reason)], ['reason' => ['required', 'string', 'max:500']])->validate();
            }
            $this->counts->update($id, ['status' => $status, 'version' => $version + 1, 'reason' => trim($reason) ?: null,
                'active_warehouse_id' => $status === 'cancelled' ? null : $record['warehouse_id']]);
            $this->counts->audit($id, $status, $actorId);
        }, 3);
    }

    public function save(string $id, int $version, array $lines, int $actorId): void
    {
        Validator::make(['lines' => $lines], ['lines' => ['required', 'array', 'list', 'max:10000'],
            'lines.*' => ['required', 'array:id,quantity'], 'lines.*.id' => ['required', 'ulid', 'distinct:ignore_case'],
            'lines.*.quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D']])->validate();
        DB::transaction(function () use ($id, $version, $lines, $actorId): void {
            $record = $this->lock($id, $version);
            if ($record['status'] !== 'counting') {
                throw new InvalidArgumentException('Hasil hitung hanya dapat diubah saat penghitungan.');
            }
            $owned = array_column($record['lines'], null, 'id');
            foreach ($lines as $line) {
                $lineId = strtolower($line['id']);
                if (! isset($owned[$lineId])) {
                    throw new InvalidArgumentException('Baris bukan bagian dari sesi opname.');
                }
                $this->counts->updateLine($lineId, ['counted_quantity' => (string) BigDecimal::of($line['quantity'])->toScale(6)]);
            }
            $this->counts->update($id, ['version' => $version + 1]);
            $this->counts->audit($id, 'counted', $actorId);
        }, 3);
    }

    public function add(string $id, int $version, string $itemId, int $actorId): void
    {
        Validator::make(['item_id' => $itemId], ['item_id' => ['required', 'ulid']])->validate();
        DB::transaction(function () use ($id, $version, $itemId, $actorId): void {
            $record = $this->lock($id, $version);
            if ($record['status'] !== 'counting') {
                throw new InvalidArgumentException('Barang hanya dapat ditambahkan saat penghitungan.');
            }
            $itemId = strtolower($itemId);
            if (in_array($itemId, array_column($record['lines'], 'item_id'), true)) {
                throw new InvalidArgumentException('Barang sudah ada pada opname.');
            }
            $balances = array_column($this->counts->balances($record['warehouse_id']), null, 'item_id');
            $this->insertLine($id, $itemId, (string) ($balances[$itemId]['quantity'] ?? '0'));
            $this->counts->update($id, ['version' => $version + 1]);
            $this->counts->audit($id, 'item_added', $actorId);
        }, 3);
    }

    public function lock(string $id, int $version): array
    {
        $initial = $this->counts->find($id);
        $this->warehouses->findActive($initial['warehouse_id'], lock: true) ?? throw new InvalidArgumentException('Gudang harus aktif.');
        $record = $this->counts->find($id, true);
        if ($record['version'] !== $version) {
            throw new InvalidArgumentException('Sesi berubah di tab lain. Muat ulang sebelum melanjutkan.');
        }

        return $record;
    }

    private function insertLine(string $id, string $itemId, string $quantity): void
    {
        $item = $this->items->findActive($itemId) ?? throw new InvalidArgumentException('Aktifkan kembali barang bersaldo sebelum opname.');
        $unit = $this->units->findActiveById($item->baseUnitId) ?? throw new InvalidArgumentException('Satuan dasar harus aktif.');
        $this->counts->addLine(['id' => strtolower((string) Str::ulid()), 'count_id' => $id, 'item_id' => $item->id,
            'base_unit_id' => $unit->id, 'sku' => $item->sku, 'item_name' => $item->name, 'base_unit_name' => $unit->name,
            'system_quantity' => (string) BigDecimal::of($quantity)->toScale(6)]);
    }
}
