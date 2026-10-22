<?php

namespace App\Modules\Purchasing\Returns\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\PurchaseReturnStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseReturnStockRequest;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Purchasing\Returns\Domain\ValueObjects\ReturnQuantity;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class PostPurchaseReturnDraft
{
    public function __construct(private ReturnDraftRepository $drafts, private ReturnPurchaseLookup $purchases,
        private PurchaseReturnStockWriter $stock) {}

    public function handle(string $id, mixed $version, ?int $actorId = null): array
    {
        Validator::make(['version' => $version, 'actor_id' => $actorId], [
            'version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'actor_id' => ['nullable', 'integer', 'min:1'],
        ])->validate();
        try {
            return DB::transaction(function () use ($id, $version, $actorId): array {
                $record = $this->drafts->lock(strtolower($id));
                if ($record['status'] === 'posted') {
                    if ($record['posted_from_version'] !== (int) $version) {
                        throw ValidationException::withMessages(['version' => 'Retur sudah diposting dengan revisi berbeda. Muat ulang halaman.']);
                    }
                    if ($record['stock_issue_id'] === null || $record['posted_at'] === null) {
                        throw new RuntimeException('Metadata retur posted tidak lengkap.');
                    }

                    return $this->result($record);
                }
                if ($record['status'] !== 'draft' || $record['version'] !== (int) $version) {
                    throw ValidationException::withMessages(['version' => 'Draft retur sudah berubah. Muat ulang sebelum posting.']);
                }
                $purchase = $this->purchases->findEligible($record['purchase_order_id'])
                    ?? throw new InvalidArgumentException('Pembelian diterima asal tidak ditemukan.');
                if ($record['lines'] === [] || count($record['lines']) > 100
                    || ($record['snapshot']['id'] ?? null) !== $purchase->header['id']
                    || ($record['snapshot']['warehouse_id'] ?? null) !== $purchase->header['warehouse_id']
                    || ($record['snapshot']['supplier_id'] ?? null) !== $purchase->header['supplier_id']) {
                    throw new InvalidArgumentException('Snapshot header draft retur tidak valid.');
                }
                $source = array_column($purchase->lines, null, 'id');
                $lines = [];
                foreach ($record['lines'] as $line) {
                    $original = $source[$line['purchase_order_line_id']] ?? throw new InvalidArgumentException('Baris pembelian asal tidak ditemukan.');
                    $quantity = ReturnQuantity::from($line['quantity'], $original['quantity'], $original['conversion_ratio']);
                    foreach (['id', 'item_id', 'unit_id', 'base_unit_id', 'conversion_ratio', 'unit_cost', 'quantity', 'base_quantity', 'total_value'] as $key) {
                        if (($line['snapshot'][$key] ?? null) !== $original[$key]) {
                            throw new InvalidArgumentException('Snapshot baris retur berbeda dari pembelian asal.');
                        }
                    }
                    if ($quantity->baseQuantity !== $line['base_quantity']) {
                        throw new InvalidArgumentException('Kuantitas dasar draft tidak sesuai pembelian asal.');
                    }
                    $lines[] = ['source_line_id' => $line['id'], 'purchase_line_id' => $line['purchase_order_line_id'],
                        'base_quantity' => $quantity->baseQuantity];
                }
                $issue = $this->stock->issue(new PurchaseReturnStockRequest($purchase->header['warehouse_id'], $record['id'],
                    $record['number'], $purchase->header['id'], $lines, $actorId));
                $this->drafts->post($record['id'], $issue->id, $issue->postedAt, (int) $version, $actorId);

                return $this->result($this->drafts->lock($record['id']));
            }, 3);
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['draft' => $error->getMessage()]);
        }
    }

    private function result(array $record): array
    {
        return Arr::only($record, ['id', 'status', 'version', 'stock_issue_id', 'posted_at']);
    }
}
