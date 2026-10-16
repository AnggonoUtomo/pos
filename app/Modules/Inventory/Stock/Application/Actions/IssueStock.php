<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Contracts\SaleStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Application\DTOs\SaleStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\SaleStockResult;
use App\Modules\Inventory\Stock\Domain\ValueObjects\FifoAllocation;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class IssueStock implements SaleStockWriter
{
    public function __construct(private StockRepository $stock, private WarehouseLookup $warehouses,
        private ItemStockLookup $items, private UnitLookup $units) {}

    public function issue(SaleStockRequest $request): SaleStockResult
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Sale stock writer memerlukan transaction caller.');
        }
        $result = $this->handle(['warehouse_id' => $request->warehouseId, 'source_id' => $request->sourceId,
            'source_number' => $request->sourceNumber, 'lines' => $request->lines], $request->actorId);

        return new SaleStockResult($result['id'], array_column($result['lines'], 'total_value', 'source_line_id'));
    }

    public function handle(array $input, ?int $actorId = null): array
    {
        $input = $this->normalize($input);
        $hash = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($input, $hash, $actorId): array {
                $warehouse = $this->warehouses->findActive($input['warehouse_id'], lock: true)
                    ?? throw new InvalidArgumentException('Gudang harus aktif dan tersedia.');
                $existing = $this->stock->lockIssueBySource($input['source_id']);
                if ($existing !== null) {
                    if (! hash_equals($existing['request_hash'], $hash)) {
                        throw new InvalidArgumentException('Sumber pengeluaran sudah dipakai dengan isi berbeda.');
                    }

                    return $existing;
                }
                $itemIds = array_values(array_unique(array_column($input['lines'], 'item_id')));
                sort($itemIds);
                $unitIds = [];
                foreach ($itemIds as $id) {
                    $item = $this->items->findActive($id) ?? throw new InvalidArgumentException('Barang tidak tersedia.');
                    $unitIds[] = $item->baseUnitId;
                }
                $unitIds = array_values(array_unique($unitIds));
                sort($unitIds);
                $units = $items = [];
                foreach ($unitIds as $id) {
                    $units[$id] = $this->units->findActiveById($id, lock: true) ?? throw new InvalidArgumentException('Satuan dasar tidak aktif.');
                }
                foreach ($itemIds as $id) {
                    $items[$id] = $this->items->findActive($id, lock: true) ?? throw new InvalidArgumentException('Barang tidak aktif.');
                }
                $header = ['source_type' => 'sale', 'source_id' => $input['source_id'], 'source_number' => $input['source_number'],
                    'request_hash' => $hash, 'warehouse_id' => $warehouse->id, 'warehouse_code' => $warehouse->code,
                    'warehouse_name' => $warehouse->name, 'posted_at' => now(), 'posted_by' => $actorId];
                $issueId = $this->stock->createIssue($header);
                foreach ($input['lines'] as $index => $line) {
                    [$balance, $layers] = $this->stock->lockIssueInventory($warehouse->id, $line['item_id']);
                    if ($balance === null || BigDecimal::of($balance['quantity'])->isLessThan($line['base_quantity'])) {
                        throw new InvalidArgumentException('Stok gudang tidak mencukupi untuk pengeluaran.');
                    }
                    $allocation = FifoAllocation::calculate($line['base_quantity'], $layers);
                    $quantity = $value = BigDecimal::zero()->toScale(6);
                    foreach ($layers as $layer) {
                        $quantity = $quantity->plus($layer['remaining_quantity']);
                        $value = $value->plus($layer['remaining_value']);
                    }
                    if (! $quantity->isEqualTo($balance['quantity']) || ! $value->isEqualTo($balance['total_value'])) {
                        throw new InvalidArgumentException('Saldo dan sisa layer FIFO tidak konsisten.');
                    }
                    $item = $items[$line['item_id']];
                    $row = ['source_line_id' => $line['source_line_id'], 'line_number' => $index + 1,
                        'item_id' => $item->id, 'base_unit_id' => $item->baseUnitId, 'sku' => $item->sku,
                        'item_name' => $item->name, 'base_unit_name' => $units[$item->baseUnitId]->name];
                    $after = ['quantity' => (string) $quantity->minus($allocation->quantity), 'total_value' => (string) $value->minus($allocation->value)];
                    $this->stock->postIssueLine($issueId, $header, $row, $allocation, $after);
                }
                $this->stock->auditIssue($issueId, $actorId);

                return $this->stock->lockIssueBySource($input['source_id']);
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            throw new InvalidArgumentException('Sumber atau baris pengeluaran sudah digunakan. Muat ulang transaksi.', previous: $exception);
        }
    }

    private function normalize(array $input): array
    {
        $data = Validator::make($input, ['warehouse_id' => ['required', 'ulid'], 'source_id' => ['required', 'ulid'],
            'source_number' => ['required', 'string', 'max:32'], 'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*' => ['required', 'array:source_line_id,item_id,base_quantity'], 'lines.*.source_line_id' => ['required', 'ulid'],
            'lines.*.item_id' => ['required', 'ulid'], 'lines.*.base_quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D']])->validate();
        $seen = $lines = [];
        foreach ($data['lines'] as $row) {
            $id = strtolower($row['source_line_id']);
            $quantity = BigDecimal::of($row['base_quantity'])->toScale(6);
            if (isset($seen[$id]) || ! $quantity->isPositive()) {
                throw new InvalidArgumentException('Baris sumber harus unik dan kuantitas dasar positif.');
            }
            $seen[$id] = true;
            $lines[] = ['source_line_id' => $id, 'item_id' => strtolower($row['item_id']), 'base_quantity' => (string) $quantity];
        }
        if (trim($data['source_number']) === '') {
            throw new InvalidArgumentException('Nomor sumber wajib diisi.');
        }

        return ['warehouse_id' => strtolower($data['warehouse_id']), 'source_id' => strtolower($data['source_id']),
            'source_number' => trim($data['source_number']), 'lines' => $lines];
    }
}
