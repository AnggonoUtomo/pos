<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Contracts\PurchaseReturnStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseReturnStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseReturnStockResult;
use App\Modules\Inventory\Stock\Domain\ValueObjects\FifoAllocation;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use LogicException;

final class IssuePurchaseReturnStock implements PurchaseReturnStockWriter
{
    public function __construct(private StockRepository $stock, private WarehouseLookup $warehouses,
        private ItemStockLookup $items, private UnitLookup $units) {}

    public function issue(PurchaseReturnStockRequest $request): PurchaseReturnStockResult
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Writer retur pembelian memerlukan transaction caller.');
        }
        $data = $this->normalize($request);
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $existing = $this->stock->lockIssueBySource($data['source_id'], 'purchase_return');
        if ($existing !== null) {
            return $this->replay($existing, $hash);
        }

        try {
            $warehouse = $this->warehouses->findActive($data['warehouse_id'], lock: true)
                ?? throw new InvalidArgumentException('Gudang asal harus aktif.');
            $existing = $this->stock->lockIssueBySource($data['source_id'], 'purchase_return');
            if ($existing !== null) {
                return $this->replay($existing, $hash);
            }
            $source = $this->stock->purchaseSourceLayers($data['purchase_id'])
                ?? throw new InvalidArgumentException('Penerimaan pembelian asal tidak ditemukan.');
            if ($source['warehouse_id'] !== $warehouse->id) {
                throw new InvalidArgumentException('Gudang retur berbeda dari penerimaan asal.');
            }
            $sourceLines = array_column($source['lines'], null, 'source_line_id');
            $selected = [];
            foreach ($data['lines'] as $line) {
                $origin = $sourceLines[$line['purchase_line_id']] ?? throw new InvalidArgumentException('Baris bukan bagian dari penerimaan pembelian asal.');
                if ($origin['layer_warehouse_id'] !== $warehouse->id || $origin['layer_item_id'] !== $origin['item_id']
                    || ! BigDecimal::of((string) $origin['quantity'])->isEqualTo((string) $origin['original_quantity'])) {
                    throw new InvalidArgumentException('Jejak layer penerimaan pembelian tidak konsisten.');
                }
                $selected[] = $line + ['origin' => $origin];
            }
            $itemIds = array_values(array_unique(array_column(array_column($selected, 'origin'), 'item_id')));
            $unitIds = array_values(array_unique(array_column(array_column($selected, 'origin'), 'base_unit_id')));
            sort($itemIds);
            sort($unitIds);
            $units = $items = [];
            foreach ($unitIds as $id) {
                $units[$id] = $this->units->findActiveById($id, lock: true)
                    ?? throw new InvalidArgumentException('Satuan dasar tidak aktif.');
            }
            foreach ($itemIds as $id) {
                $items[$id] = $this->items->findActive($id, lock: true)
                    ?? throw new InvalidArgumentException('Barang retur tidak aktif.');
            }
            usort($selected, fn ($a, $b) => [$a['origin']['item_id'], $a['purchase_line_id']] <=> [$b['origin']['item_id'], $b['purchase_line_id']]);
            $header = ['source_type' => 'purchase_return', 'source_id' => $data['source_id'], 'source_number' => $data['source_number'],
                'request_hash' => $hash, 'warehouse_id' => $warehouse->id, 'warehouse_code' => $warehouse->code,
                'warehouse_name' => $warehouse->name, 'posted_at' => now()->startOfSecond(), 'posted_by' => $request->actorId];
            $issueId = $this->stock->createIssue($header);
            foreach ($selected as $index => $line) {
                $origin = $line['origin'];
                $item = $items[$origin['item_id']];
                if ($item->baseUnitId !== $origin['base_unit_id']) {
                    throw new InvalidArgumentException('Satuan dasar sumber tidak sesuai barang.');
                }
                [$balance, $layers] = $this->stock->lockIssueInventory($warehouse->id, $item->id);
                if ($balance === null) {
                    throw new InvalidArgumentException('Stok gudang asal tidak tersedia.');
                }
                $quantity = $value = BigDecimal::zero()->toScale(6);
                $originLayer = null;
                foreach ($layers as $layer) {
                    $quantity = $quantity->plus((string) $layer['remaining_quantity']);
                    $value = $value->plus((string) $layer['remaining_value']);
                    if ($layer['id'] === $origin['layer_id']) {
                        $originLayer = $layer;
                    }
                }
                if (! $quantity->isEqualTo((string) $balance['quantity']) || ! $value->isEqualTo((string) $balance['total_value'])) {
                    throw new InvalidArgumentException('Saldo dan layer FIFO tidak konsisten.');
                }
                if ($originLayer === null || $quantity->isLessThan($line['base_quantity'])) {
                    throw new InvalidArgumentException('Sisa layer penerimaan asal tidak mencukupi.');
                }
                $allocation = FifoAllocation::calculate($line['base_quantity'], [$originLayer]);
                $this->stock->postIssueLine($issueId, $header, ['source_line_id' => $line['source_line_id'],
                    'line_number' => $index + 1, 'item_id' => $item->id, 'base_unit_id' => $item->baseUnitId,
                    'sku' => $item->sku, 'item_name' => $item->name, 'base_unit_name' => $units[$item->baseUnitId]->name],
                    $allocation, ['quantity' => (string) $quantity->minus($allocation->quantity), 'total_value' => (string) $value->minus($allocation->value)]);
            }
            $this->stock->auditIssue($issueId, $request->actorId);

            return new PurchaseReturnStockResult($issueId, $header['posted_at']->utc()->toISOString());
        } catch (UniqueConstraintViolationException $error) {
            throw new InvalidArgumentException('Sumber atau baris pengeluaran sudah digunakan. Muat ulang transaksi.', previous: $error);
        }
    }

    private function replay(array $existing, string $hash): PurchaseReturnStockResult
    {
        if (! hash_equals($existing['request_hash'], $hash)) {
            throw new InvalidArgumentException('Sumber retur sudah dipakai dengan isi berbeda.');
        }

        return new PurchaseReturnStockResult($existing['id'], Carbon::parse($existing['posted_at'], config('app.timezone'))->utc()->toISOString());
    }

    private function normalize(PurchaseReturnStockRequest $request): array
    {
        $data = Validator::make(['warehouse_id' => $request->warehouseId, 'source_id' => $request->sourceId,
            'source_number' => trim($request->sourceNumber), 'purchase_id' => $request->purchaseId, 'lines' => $request->lines,
            'actor_id' => $request->actorId], [
                'warehouse_id' => ['required', 'ulid'], 'source_id' => ['required', 'ulid'],
                'source_number' => ['required', 'string', 'max:32'], 'purchase_id' => ['required', 'ulid'],
                'actor_id' => ['nullable', 'integer', 'min:1'], 'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
                'lines.*' => ['required', 'array:source_line_id,purchase_line_id,base_quantity'],
                'lines.*.source_line_id' => ['required', 'ulid', 'distinct:ignore_case'],
                'lines.*.purchase_line_id' => ['required', 'ulid', 'distinct:ignore_case'],
                'lines.*.base_quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'],
            ])->validate();
        unset($data['actor_id']);
        foreach (['warehouse_id', 'source_id', 'purchase_id'] as $field) {
            $data[$field] = strtolower($data[$field]);
        }
        foreach ($data['lines'] as &$line) {
            foreach (['source_line_id', 'purchase_line_id'] as $field) {
                $line[$field] = strtolower($line[$field]);
            }
            $quantity = BigDecimal::of($line['base_quantity'])->toScale(6);
            if (! $quantity->isPositive()) {
                throw new InvalidArgumentException('Kuantitas retur harus positif.');
            }
            $line['base_quantity'] = (string) $quantity;
        }
        unset($line);
        usort($data['lines'], fn ($a, $b) => strcmp($a['purchase_line_id'], $b['purchase_line_id']));

        return $data;
    }
}
