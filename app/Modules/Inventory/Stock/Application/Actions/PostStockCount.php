<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\AdjustmentRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockCountRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockReceiptRepository;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

final class PostStockCount
{
    public function __construct(private StockCountRepository $counts, private WarehouseLookup $warehouses,
        private StockReceiptRepository $inventory, private WriteOperationalStock $stock, private AdjustmentRepository $adjustments) {}

    public function handle(string $id, int $version, string $reason, array $costs, int $actorId): void
    {
        Validator::make(['reason' => trim($reason), 'costs' => $costs], [
            'reason' => ['required', 'string', 'max:500'], 'costs' => ['present', 'array', 'list', 'max:10000'],
            'costs.*' => ['required', 'array:id,unit_cost'], 'costs.*.id' => ['required', 'ulid', 'distinct:ignore_case'],
            'costs.*.unit_cost' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D']])->validate();
        $normalized = [];
        foreach ($costs as $cost) {
            $value = BigDecimal::of($cost['unit_cost'])->toScale(6);
            if (! $value->isPositive()) {
                throw new InvalidArgumentException('Harga pokok selisih tambah wajib positif.');
            }
            $normalized[strtolower($cost['id'])] = (string) $value;
        }
        ksort($normalized);
        $hash = hash('sha256', json_encode([trim($reason), $normalized], JSON_THROW_ON_ERROR));
        DB::transaction(function () use ($id, $version, $reason, $normalized, $hash, $actorId): void {
            $initial = $this->counts->find($id);
            $this->warehouses->findActive($initial['warehouse_id'], lock: true) ?? throw new InvalidArgumentException('Gudang harus aktif.');
            $record = $this->counts->find($id, true);
            if ($record['status'] === 'posted') {
                if ($record['post_hash'] !== $hash || $record['posted_by'] !== $actorId) {
                    throw new InvalidArgumentException('Opname sudah diposting dengan isi atau actor berbeda.');
                }

                return;
            }
            if ($record['status'] !== 'review' || $record['version'] !== $version || $record['lines'] === []) {
                throw new InvalidArgumentException('Opname belum siap atau berubah. Muat ulang untuk review.');
            }
            $lines = $record['lines'];
            usort($lines, fn ($a, $b) => strcmp($a['item_id'], $b['item_id']));
            $positive = [];
            foreach ($lines as $line) {
                if ($line['counted_quantity'] === null) {
                    throw new InvalidArgumentException('Seluruh barang wajib dihitung.');
                }
                [$balance] = $this->inventory->inventory($record['warehouse_id'], $line['item_id']);
                if (! BigDecimal::of($balance['quantity'] ?? '0')->isEqualTo($line['system_quantity'])) {
                    throw new InvalidArgumentException('Saldo berubah setelah snapshot. Batalkan sesi dan hitung ulang.');
                }
                if (BigDecimal::of($line['difference'])->isPositive()) {
                    $positive[] = $line['id'];
                }
            }
            if (array_diff($positive, array_keys($normalized)) || array_diff(array_keys($normalized), $positive)) {
                throw new InvalidArgumentException('Isi harga pokok hanya untuk seluruh baris selisih tambah.');
            }
            $this->counts->update($id, ['status' => 'posting']);
            foreach ($lines as $line) {
                $difference = BigDecimal::of($line['difference']);
                if ($difference->isZero()) {
                    continue;
                }
                $direction = $difference->isPositive() ? 'in' : 'out';
                $quantity = (string) $difference->abs()->toScale(6);
                $cost = $normalized[$line['id']] ?? null;
                $value = $this->stock->adjustment($line['id'], $record['warehouse_id'], $line['item_id'], $direction,
                    $quantity, $cost, $actorId, $id);
                $this->adjustments->create(['id' => $line['id'], 'number' => 'AD-'.strtoupper($line['id']),
                    'request_hash' => $hash, 'warehouse_id' => $record['warehouse_id'], 'item_id' => $line['item_id'],
                    'direction' => $direction, 'quantity' => $quantity, 'total_value' => $value,
                    'reason' => trim($reason), 'posted_at' => now(), 'posted_by' => $actorId]);
                $this->counts->updateLine($line['id'], ['adjustment_id' => $line['id'], 'unit_cost' => $cost]);
            }
            $this->counts->update($id, ['status' => 'posted', 'version' => $version + 1, 'active_warehouse_id' => null,
                'reason' => trim($reason), 'posted_at' => now(), 'posted_by' => $actorId, 'post_hash' => $hash]);
            $this->counts->audit($id, 'posted', $actorId);
        }, 3);
    }
}
