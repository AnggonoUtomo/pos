<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Contracts\StockReceiptRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Application\Contracts\TransferStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\TransferStockRequest;
use App\Modules\Inventory\Stock\Domain\ValueObjects\FifoAllocation;
use App\Modules\Inventory\Stock\Domain\ValueObjects\ReceiptBalance;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class WriteOperationalStock implements TransferStockWriter
{
    public function __construct(private StockRepository $issues, private StockReceiptRepository $receipts,
        private WarehouseLookup $warehouses, private ItemStockLookup $items, private UnitLookup $units) {}

    public function transfer(TransferStockRequest $request): void
    {
        $this->requireTransaction();
        if ($request->sourceWarehouseId === $request->destinationWarehouseId) {
            throw new InvalidArgumentException('Gudang asal dan tujuan harus berbeda.');
        }
        $warehouseIds = [$request->sourceWarehouseId, $request->destinationWarehouseId];
        sort($warehouseIds);
        foreach ($warehouseIds as $warehouseId) {
            $this->warehouses->findActive($warehouseId, lock: true) ?? throw new InvalidArgumentException('Gudang transfer harus aktif.');
        }
        $source = $this->header($request->sourceWarehouseId, $request->id, $request->number, 'transfer', $request->actorId);
        $target = $this->header($request->destinationWarehouseId, $request->id, $request->number, 'transfer', $request->actorId);
        if ($this->issues->lockIssueBySource($request->id, 'transfer') !== null || $this->receipts->findSource($request->id, true, 'transfer') !== null) {
            throw new InvalidArgumentException('Sumber transfer sudah memiliki ledger. Muat ulang dokumen.');
        }
        $issueId = $this->issues->createIssue($source);
        $receiptId = $this->receipts->create($target);
        $lines = $request->lines;
        usort($lines, fn ($a, $b) => strcmp($a['item_id'], $b['item_id']));
        $snapshots = $this->snapshots(array_column($lines, 'item_id'));
        $number = 0;
        foreach ($lines as $index => $line) {
            $snapshot = $snapshots[$line['item_id']];
            $allocation = $this->issue($issueId, $source, $snapshot + ['source_line_id' => $line['id'], 'line_number' => $index + 1], $line['base_quantity']);
            // Separate destination layers preserve each consumed source cost and provenance.
            foreach ($allocation->allocations as $segment) {
                $this->receive($receiptId, $target, $snapshot + ['source_line_id' => strtolower((string) Str::ulid()),
                    'line_number' => ++$number, 'origin_fifo_layer_id' => $segment['layer_id'],
                    'quantity' => $segment['quantity'], 'total_value' => $segment['value']]);
            }
        }
        $this->issues->auditIssue($issueId, $request->actorId);
        $this->receipts->audit($receiptId, $request->actorId);
    }

    public function adjustment(string $id, string $warehouseId, string $itemId, string $direction,
        string $quantity, ?string $unitCost, ?int $actorId): string
    {
        $this->requireTransaction();
        $header = $this->header($warehouseId, $id, 'AD-'.strtoupper($id), 'adjustment', $actorId);
        $row = $this->snapshots([$itemId])[$itemId] + ['source_line_id' => $id, 'line_number' => 1];
        if ($direction === 'out') {
            $issueId = $this->issues->createIssue($header);
            $value = $this->issue($issueId, $header, $row, $quantity)->value;
            $this->issues->auditIssue($issueId, $actorId);

            return $value;
        }
        $value = (string) BigDecimal::of($quantity)->multipliedBy($unitCost)->toScale(6);
        $receiptId = $this->receipts->create($header);
        $this->receive($receiptId, $header, $row + ['quantity' => $quantity, 'total_value' => $value]);
        $this->receipts->audit($receiptId, $actorId);

        return $value;
    }

    private function header(string $warehouseId, string $id, string $number, string $type, ?int $actorId): array
    {
        $warehouse = $this->warehouses->findActive($warehouseId, lock: true) ?? throw new InvalidArgumentException('Gudang harus aktif.');

        return ['source_type' => $type, 'source_id' => $id, 'source_number' => $number,
            'request_hash' => hash('sha256', $type.':'.$id), 'warehouse_id' => $warehouse->id,
            'warehouse_code' => $warehouse->code, 'warehouse_name' => $warehouse->name,
            'posted_at' => now()->startOfSecond(), 'posted_by' => $actorId];
    }

    private function snapshots(array $itemIds): array
    {
        $unitIds = [];
        foreach ($itemIds as $itemId) {
            $item = $this->items->findActive($itemId) ?? throw new InvalidArgumentException('Barang harus aktif.');
            $unitIds[] = $item->baseUnitId;
        }
        $unitIds = array_values(array_unique($unitIds));
        sort($unitIds);
        $units = $snapshots = [];
        foreach ($unitIds as $id) {
            $units[$id] = $this->units->findActiveById($id, lock: true) ?? throw new InvalidArgumentException('Satuan dasar harus aktif.');
        }
        sort($itemIds);
        foreach ($itemIds as $itemId) {
            $item = $this->items->findActive($itemId, lock: true) ?? throw new InvalidArgumentException('Barang harus aktif.');
            $unit = $units[$item->baseUnitId] ?? throw new InvalidArgumentException('Satuan dasar barang berubah.');
            $snapshots[$itemId] = ['item_id' => $item->id, 'base_unit_id' => $unit->id, 'sku' => $item->sku,
                'item_name' => $item->name, 'base_unit_name' => $unit->name];
        }

        return $snapshots;
    }

    private function issue(string $id, array $header, array $row, string $quantity): FifoAllocation
    {
        [$balance, $layers] = $this->issues->lockIssueInventory($header['warehouse_id'], $row['item_id']);
        if ($balance === null || BigDecimal::of($balance['quantity'])->isLessThan($quantity)) {
            throw new InvalidArgumentException('Stok gudang tidak mencukupi.');
        }
        // Also verifies all layer quantities/values against the authoritative balance.
        $q = $v = BigDecimal::zero()->toScale(6);
        foreach ($layers as $layer) {
            $q = $q->plus($layer['remaining_quantity']);
            $v = $v->plus($layer['remaining_value']);
        }
        if (! $q->isEqualTo($balance['quantity']) || ! $v->isEqualTo($balance['total_value'])) {
            throw new InvalidArgumentException('Saldo dan layer FIFO tidak konsisten.');
        }
        $allocation = FifoAllocation::calculate($quantity, $layers);
        $this->issues->postIssueLine($id, $header, $row, $allocation, [
            'quantity' => (string) BigDecimal::of($balance['quantity'])->minus($allocation->quantity)->toScale(6),
            'total_value' => (string) BigDecimal::of($balance['total_value'])->minus($allocation->value)->toScale(6)]);

        return $allocation;
    }

    private function receive(string $id, array $header, array $row): void
    {
        [$balance, $layers] = $this->receipts->inventory($header['warehouse_id'], $row['item_id']);
        $after = ReceiptBalance::add($balance, $layers, $row['quantity'], $row['total_value'], allowZeroValue: true);
        $this->receipts->receiveLine($id, $header, $row, $after, $balance === null);
    }

    private function requireTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Operasi stok memerlukan transaction caller.');
        }
    }
}
