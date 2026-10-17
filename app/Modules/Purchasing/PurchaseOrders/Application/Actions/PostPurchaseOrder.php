<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\PurchaseStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseStockRequest;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseOrderRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class PostPurchaseOrder
{
    public function __construct(private PurchaseOrderRepository $orders, private PreparePurchaseOrder $prepare, private PurchaseStockWriter $stock) {}

    public function handle(string $id, mixed $version, ?int $actorId = null): array
    {
        Validator::make(['version' => $version, 'actor_id' => $actorId], ['version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'actor_id' => ['nullable', 'integer', 'min:1']])->validate();
        try {
            return DB::transaction(function () use ($id, $version, $actorId): array {
                $record = $this->orders->lock(strtolower($id));
                if ($record['status'] === 'posted') {
                    if ($record['posted_from_version'] !== (int) $version) {
                        throw ValidationException::withMessages(['purchase_order' => 'Dokumen sudah diterima dengan revisi berbeda. Muat ulang halaman.']);
                    }
                    if ($record['stock_receipt_id'] === null || $record['posted_at'] === null) {
                        throw new RuntimeException('Metadata penerimaan posted tidak lengkap.');
                    }

                    return $this->result($record);
                }
                if ($record['status'] !== 'draft' || $record['version'] !== (int) $version) {
                    throw ValidationException::withMessages(['purchase_order' => 'Draft sudah berubah. Muat ulang halaman sebelum menerima barang.']);
                }
                [$header, $lines] = $this->prepare->handle($record);
                $old = array_column($record['lines'], null, 'item_id');
                $stockLines = [];
                foreach ($lines as &$line) {
                    $previous = $old[$line['item_id']];
                    foreach (['base_unit_id', 'conversion_ratio', 'base_quantity', 'total_value'] as $key) {
                        if ($line[$key] !== $previous[$key]) {
                            throw ValidationException::withMessages(['purchase_order' => 'Konversi atau nilai draft berubah. Edit dan simpan ulang sebelum menerima barang.']);
                        }
                    }
                    $line['id'] = $previous['id'];
                    $stockLines[] = ['source_line_id' => $previous['id'], 'item_id' => $line['item_id'], 'base_unit_id' => $line['base_unit_id'], 'base_quantity' => $line['base_quantity'], 'total_value' => $line['total_value']];
                }
                unset($line);
                if ($record['total_value'] !== $header['total_value']) {
                    throw ValidationException::withMessages(['purchase_order' => 'Total draft tidak konsisten. Simpan ulang sebelum menerima barang.']);
                }
                $receipt = $this->stock->receive(new PurchaseStockRequest($record['warehouse_id'], $record['id'], $record['number'], $stockLines, $actorId));
                $this->orders->post($record['id'], $header, $lines, $receipt->id, $receipt->postedAt, (int) $version, $actorId);

                return $this->result($this->orders->lock($record['id']));
            }, 3);
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['purchase_order' => $error->getMessage()]);
        }
    }

    private function result(array $record): array
    {
        return Arr::only($record, ['id', 'status', 'version', 'stock_receipt_id', 'posted_at']);
    }
}
