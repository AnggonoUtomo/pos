<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Contracts\PurchaseStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\StockReceiptRepository;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseStockResult;
use App\Modules\Inventory\Stock\Domain\ValueObjects\ReceiptBalance;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use LogicException;

class ReceivePurchaseStock implements PurchaseStockWriter
{
    public function __construct(private StockReceiptRepository $receipts, private WarehouseLookup $warehouses, private ItemStockLookup $items, private UnitLookup $units) {}

    public function receive(PurchaseStockRequest $request): PurchaseStockResult
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Purchase stock writer memerlukan transaction caller.');
        }
        $data = $this->normalize($request);
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $existing = $this->receipts->findSource($data['source_id']);
        if ($existing !== null) {
            return $this->replay($existing, $hash);
        }
        $warehouse = $this->warehouses->findActive($data['warehouse_id'], lock: true) ?? throw new InvalidArgumentException('Gudang penerimaan harus aktif.');
        $existing = $this->receipts->findSource($data['source_id'], lock: true);
        if ($existing !== null) {
            return $this->replay($existing, $hash);
        }
        $unitIds = array_values(array_unique(array_column($data['lines'], 'base_unit_id')));
        sort($unitIds);
        $units = [];
        foreach ($unitIds as $id) {
            $units[$id] = $this->units->findActiveById($id, lock: true) ?? throw new InvalidArgumentException('Satuan dasar penerimaan harus aktif.');
        }
        $items = [];
        foreach ($data['lines'] as $line) {
            $item = $this->items->findActive($line['item_id'], lock: true) ?? throw new InvalidArgumentException('Barang penerimaan harus aktif.');
            if ($item->baseUnitId !== $line['base_unit_id']) {
                throw new InvalidArgumentException('Satuan dasar sumber tidak sesuai barang.');
            }
            $items[$item->id] = $item;
        }
        $header = ['source_type' => 'purchase', 'source_id' => $data['source_id'], 'source_number' => $data['source_number'], 'request_hash' => $hash,
            'warehouse_id' => $warehouse->id, 'warehouse_code' => $warehouse->code, 'warehouse_name' => $warehouse->name,
            'posted_at' => now()->startOfSecond(), 'posted_by' => $request->actorId];
        try {
            $id = $this->receipts->create($header);
            foreach ($data['lines'] as $index => $line) {
                [$balance, $layers] = $this->receipts->inventory($warehouse->id, $line['item_id']);
                $after = ReceiptBalance::add($balance, $layers, $line['base_quantity'], $line['total_value']);
                $item = $items[$line['item_id']];
                $this->receipts->receiveLine($id, $header, ['source_line_id' => $line['source_line_id'], 'line_number' => $index + 1,
                    'item_id' => $item->id, 'base_unit_id' => $item->baseUnitId, 'sku' => $item->sku, 'item_name' => $item->name,
                    'base_unit_name' => $units[$item->baseUnitId]->name, 'quantity' => $line['base_quantity'], 'total_value' => $line['total_value']], $after, $balance === null);
            }
            $this->receipts->audit($id, $request->actorId);
        } catch (UniqueConstraintViolationException $error) {
            throw new InvalidArgumentException('Sumber atau baris penerimaan sudah digunakan.', previous: $error);
        }

        return new PurchaseStockResult($id, $header['posted_at']->utc()->toISOString());
    }

    private function replay(array $existing, string $hash): PurchaseStockResult
    {
        if (! hash_equals($existing['request_hash'], $hash)) {
            throw new InvalidArgumentException('Sumber penerimaan sudah digunakan dengan isi berbeda.');
        }

        return new PurchaseStockResult($existing['id'], Carbon::parse($existing['posted_at'], config('app.timezone'))->utc()->toISOString());
    }

    private function normalize(PurchaseStockRequest $request): array
    {
        $input = Validator::make(['warehouse_id' => $request->warehouseId, 'source_id' => $request->sourceId, 'source_number' => trim($request->sourceNumber), 'lines' => $request->lines], [
            'warehouse_id' => ['required', 'ulid'], 'source_id' => ['required', 'ulid'], 'source_number' => ['required', 'string', 'max:32'],
            'lines' => ['required', 'array', 'list', 'min:1', 'max:100'], 'lines.*' => ['required', 'array:source_line_id,item_id,base_unit_id,base_quantity,total_value'],
            'lines.*.source_line_id' => ['required', 'ulid'], 'lines.*.item_id' => ['required', 'ulid'], 'lines.*.base_unit_id' => ['required', 'ulid'],
            'lines.*.base_quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'],
            'lines.*.total_value' => ['required', 'string', 'regex:/^\d{1,24}(?:\.\d{1,6})?$/D'],
        ])->validate();
        $seen = $items = $lines = [];
        foreach ($input['lines'] as $line) {
            foreach (['source_line_id', 'item_id', 'base_unit_id'] as $key) {
                $line[$key] = strtolower($line[$key]);
            }
            $q = BigDecimal::of($line['base_quantity'])->toScale(6);
            $v = BigDecimal::of($line['total_value'])->toScale(6);
            if (isset($seen[$line['source_line_id']]) || isset($items[$line['item_id']]) || ! $q->isPositive() || ! $v->isPositive()) {
                throw new InvalidArgumentException('Baris/item harus unik dan kuantitas/nilai penerimaan positif.');
            }
            $seen[$line['source_line_id']] = $items[$line['item_id']] = true;
            $lines[] = ['source_line_id' => $line['source_line_id'], 'item_id' => $line['item_id'], 'base_unit_id' => $line['base_unit_id'], 'base_quantity' => (string) $q, 'total_value' => (string) $v];
        }
        usort($lines, fn ($a, $b) => strcmp($a['item_id'], $b['item_id']));

        return ['warehouse_id' => strtolower($input['warehouse_id']), 'source_id' => strtolower($input['source_id']), 'source_number' => $input['source_number'], 'lines' => $lines];
    }
}
