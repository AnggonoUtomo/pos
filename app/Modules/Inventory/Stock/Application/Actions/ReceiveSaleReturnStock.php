<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Contracts\SaleReturnStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\StockReceiptRepository;
use App\Modules\Inventory\Stock\Application\DTOs\SaleReturnStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\SaleReturnStockResult;
use App\Modules\Inventory\Stock\Domain\ValueObjects\ReceiptBalance;
use App\Modules\Inventory\Stock\Domain\ValueObjects\ReturnAllocation;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use LogicException;

final class ReceiveSaleReturnStock implements SaleReturnStockWriter
{
    public function __construct(private StockReceiptRepository $receipts, private WarehouseLookup $warehouses,
        private ItemStockLookup $items, private UnitLookup $units) {}

    public function receive(SaleReturnStockRequest $request): SaleReturnStockResult
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Return stock writer memerlukan transaction caller.');
        }
        $data = $this->normalize($request);
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $existing = $this->receipts->findSource($data['source_id'], sourceType: 'sale_return');
        if ($existing !== null) {
            return $this->replay($existing, $hash);
        }
        $warehouse = $this->warehouses->findActive($data['warehouse_id'], lock: true) ?? throw new InvalidArgumentException('Gudang asal harus aktif.');
        $existing = $this->receipts->findSource($data['source_id'], true, 'sale_return');
        if ($existing !== null) {
            return $this->replay($existing, $hash);
        }
        $sale = $this->receipts->saleSource($data['invoice_id']);
        if ($sale === null || $sale['warehouse_id'] !== $warehouse->id) {
            throw new InvalidArgumentException('Pengeluaran penjualan pada gudang asal tidak ditemukan.');
        }
        $source = array_column($sale['lines'], null, 'source_line_id');
        $selected = [];
        foreach ($data['lines'] as $line) {
            $selected[] = $source[$line['sale_line_id']] ?? throw new InvalidArgumentException('Baris bukan bagian dari pengeluaran penjualan asal.');
        }
        $unitIds = array_values(array_unique(array_column($selected, 'base_unit_id')));
        sort($unitIds);
        foreach ($unitIds as $id) {
            $this->units->findActiveById($id, lock: true) ?? throw new InvalidArgumentException('Satuan dasar harus aktif.');
        }
        $itemIds = array_values(array_unique(array_column($selected, 'item_id')));
        sort($itemIds);
        $items = [];
        foreach ($itemIds as $id) {
            $items[$id] = $this->items->findActive($id, lock: true) ?? throw new InvalidArgumentException('Barang retur harus aktif.');
        }
        usort($data['lines'], fn ($a, $b) => [$source[$a['sale_line_id']]['item_id'], $a['sale_line_id']] <=> [$source[$b['sale_line_id']]['item_id'], $b['sale_line_id']]);
        $header = ['source_type' => 'sale_return', 'source_id' => $data['source_id'], 'source_number' => $data['source_number'], 'request_hash' => $hash,
            'warehouse_id' => $warehouse->id, 'warehouse_code' => $warehouse->code, 'warehouse_name' => $warehouse->name,
            'posted_at' => now()->startOfSecond(), 'posted_by' => $request->actorId];
        $id = $this->receipts->create($header);
        foreach ($data['lines'] as $index => $line) {
            $original = $source[$line['sale_line_id']];
            if ($items[$original['item_id']]->baseUnitId !== $original['base_unit_id']) {
                throw new InvalidArgumentException('Satuan dasar berbeda dari penjualan asal.');
            }
            $allocation = ReturnAllocation::calculate($line['base_quantity'], (string) $original['quantity'], (string) $original['total_value'], $this->receipts->consumptions($original['id']));
            [$balance, $layers] = $this->receipts->inventory($warehouse->id, $original['item_id']);
            $after = ReceiptBalance::add($balance, $layers, $allocation->quantity, $allocation->value, allowZeroValue: true);
            $receiptLine = $this->receipts->receiveLine($id, $header, ['source_line_id' => $line['source_line_id'], 'line_number' => $index + 1,
                'item_id' => $original['item_id'], 'base_unit_id' => $original['base_unit_id'], 'sku' => $original['sku'],
                'item_name' => $original['item_name'], 'base_unit_name' => $original['base_unit_name'],
                'quantity' => $allocation->quantity, 'total_value' => $allocation->value], $after, $balance === null);
            $this->receipts->recordReturnAllocations($receiptLine, $allocation->allocations);
        }
        $this->receipts->audit($id, $request->actorId);

        return new SaleReturnStockResult($id, $header['posted_at']->utc()->toISOString());
    }

    private function replay(array $record, string $hash): SaleReturnStockResult
    {
        if (! hash_equals($record['request_hash'], $hash)) {
            throw new InvalidArgumentException('Sumber retur sudah dipakai dengan isi berbeda.');
        }

        return new SaleReturnStockResult($record['id'], Carbon::parse($record['posted_at'], config('app.timezone'))->utc()->toISOString());
    }

    private function normalize(SaleReturnStockRequest $request): array
    {
        $input = Validator::make(['source_id' => $request->sourceId, 'source_number' => trim($request->sourceNumber), 'invoice_id' => $request->invoiceId,
            'warehouse_id' => $request->warehouseId, 'lines' => $request->lines, 'actor_id' => $request->actorId], [
                'source_id' => ['required', 'ulid'], 'source_number' => ['required', 'string', 'max:32'], 'warehouse_id' => ['required', 'ulid'], 'invoice_id' => ['required', 'ulid'],
                'actor_id' => ['nullable', 'integer', 'min:1'], 'lines' => ['required', 'array', 'list', 'min:1', 'max:100'], 'lines.*' => ['required', 'array:source_line_id,sale_line_id,base_quantity'],
                'lines.*.source_line_id' => ['required', 'ulid', 'distinct:ignore_case'], 'lines.*.sale_line_id' => ['required', 'ulid', 'distinct:ignore_case'],
                'lines.*.base_quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D']])->validate();
        unset($input['actor_id']);
        foreach (['source_id', 'invoice_id', 'warehouse_id'] as $key) {
            $input[$key] = strtolower($input[$key]);
        }
        foreach ($input['lines'] as &$line) {
            $q = BigDecimal::of($line['base_quantity'])->toScale(6);
            if (! $q->isPositive()) {
                throw new InvalidArgumentException('Kuantitas retur harus positif.');
            }
            $line = ['source_line_id' => strtolower($line['source_line_id']), 'sale_line_id' => strtolower($line['sale_line_id']), 'base_quantity' => (string) $q];
        }
        unset($line);
        usort($input['lines'], fn ($a, $b) => strcmp($a['sale_line_id'], $b['sale_line_id']));

        return $input;
    }
}
