<?php

namespace App\Modules\Sales\Returns\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\SaleReturnStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\SaleReturnStockRequest;
use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Sales\Returns\Domain\ValueObjects\ReturnQuantity;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class PostReturnDraft
{
    public function __construct(private ReturnDraftRepository $drafts, private ReturnInvoiceLookup $invoices, private SaleReturnStockWriter $stock) {}

    public function handle(string $id, mixed $version, ?int $actorId = null): array
    {
        Validator::make(['version' => $version, 'actor_id' => $actorId], ['version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'actor_id' => ['nullable', 'integer', 'min:1']])->validate();
        try {
            return DB::transaction(function () use ($id, $version, $actorId): array {
                $record = $this->drafts->lock(strtolower($id));
                if ($record['status'] === 'posted') {
                    if ($record['posted_from_version'] !== (int) $version) {
                        throw ValidationException::withMessages(['version' => 'Retur sudah diterima dengan revisi berbeda. Muat ulang halaman.']);
                    }
                    if ($record['stock_receipt_id'] === null || $record['posted_at'] === null) {
                        throw new RuntimeException('Metadata retur posted tidak lengkap.');
                    }

                    return $this->result($record);
                }
                if ($record['status'] !== 'draft' || $record['version'] !== (int) $version) {
                    throw ValidationException::withMessages(['version' => 'Draft sudah berubah. Muat ulang sebelum menerima barang.']);
                }
                $invoice = $this->invoices->find($record['invoice_id']) ?? throw new InvalidArgumentException('Invoice asal tidak ditemukan.');
                if (($record['snapshot']['warehouse_id'] ?? null) !== $invoice->header['warehouse_id'] || $record['lines'] === [] || count($record['lines']) > 100) {
                    throw new InvalidArgumentException('Snapshot draft retur tidak valid.');
                }
                $source = array_column($invoice->lines, null, 'id');
                $lines = [];
                foreach ($record['lines'] as $line) {
                    $original = $source[$line['invoice_line_id']] ?? throw new InvalidArgumentException('Baris invoice asal tidak ditemukan.');
                    $quantity = ReturnQuantity::from($line['quantity'], $original['quantity'], $original['conversion_ratio']);
                    foreach (['id', 'item_id', 'unit_id', 'base_unit_id', 'conversion_ratio'] as $key) {
                        if (($line['snapshot'][$key] ?? null) !== $original[$key]) {
                            throw new InvalidArgumentException('Snapshot satuan/barang draft berbeda dari invoice asal.');
                        }
                    }
                    if ($quantity->baseQuantity !== $line['base_quantity']) {
                        throw new InvalidArgumentException('Kuantitas dasar draft tidak sesuai konversi invoice asal.');
                    }
                    $lines[] = ['source_line_id' => $line['id'], 'sale_line_id' => $line['invoice_line_id'], 'base_quantity' => $quantity->baseQuantity];
                }
                $receipt = $this->stock->receive(new SaleReturnStockRequest($invoice->header['warehouse_id'], $record['id'], $record['number'], $invoice->header['id'], $lines, $actorId));
                $this->drafts->post($record['id'], $receipt->id, $receipt->postedAt, (int) $version, $actorId);

                return $this->result($this->drafts->lock($record['id']));
            }, 3);
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['draft' => $error->getMessage()]);
        }
    }

    private function result(array $record): array
    {
        return Arr::only($record, ['id', 'status', 'version', 'stock_receipt_id', 'posted_at']);
    }
}
