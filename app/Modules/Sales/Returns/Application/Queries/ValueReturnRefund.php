<?php

namespace App\Modules\Sales\Returns\Application\Queries;

use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\Returns\Application\Contracts\ReturnRefundRepository;
use App\Modules\Sales\Returns\Domain\ValueObjects\RefundAllocation;
use App\Modules\Sales\Returns\Domain\ValueObjects\RefundInvoiceBasis;
use App\Modules\Sales\Returns\Domain\ValueObjects\ReturnQuantity;
use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class ValueReturnRefund
{
    public function __construct(private ReturnInvoiceLookup $invoices, private ReturnRefundRepository $refunds) {}

    public function handle(array $record, ?array $scope, bool $lock = false): array
    {
        if ($record['status'] !== 'posted' || $record['stock_receipt_id'] === null || $record['posted_at'] === null
            || $record['posted_from_version'] === null || $record['lines'] === [] || count($record['lines']) > 100) {
            throw new InvalidArgumentException('Hanya retur yang sudah diterima lengkap yang dapat diselesaikan.');
        }
        $revision = $scope['revision'] ?? 0;
        if ($revision < 0 || $revision >= 2147483647) {
            throw new InvalidArgumentException('Revisi refund invoice telah mencapai batas.');
        }
        $invoice = $this->invoices->find($record['invoice_id']) ?? throw new InvalidArgumentException('Invoice asal tidak tersedia.');
        $summary = $this->invoices->refundBasis($record['invoice_id']) ?? throw new InvalidArgumentException('Nilai invoice asal tidak tersedia.');
        $basis = RefundInvoiceBasis::fromSnapshot($summary->header, $summary->lines);
        if ($summary->header['id'] !== $record['invoice_id'] || ($record['snapshot']['warehouse_id'] ?? null) !== $invoice->header['warehouse_id']) {
            throw new InvalidArgumentException('Snapshot gudang atau invoice retur tidak sesuai sumber.');
        }
        $source = array_column($invoice->lines, null, 'id');
        $prior = $scope === null ? [] : $this->refunds->allocations($scope['id'], $lock);
        $lines = [];
        $total = BigDecimal::zero()->toScale(2);
        $seen = [];
        foreach ($record['lines'] as $line) {
            $key = $line['invoice_line_id'];
            $original = $source[$key] ?? throw new InvalidArgumentException('Baris invoice asal tidak tersedia.');
            $amounts = $basis->lines[$key] ?? throw new InvalidArgumentException('Nilai baris invoice asal tidak tersedia.');
            if (isset($seen[$key]) || ! is_array($line['snapshot']) || $amounts['quantity'] !== $original['quantity']) {
                throw new InvalidArgumentException('Snapshot baris retur tidak konsisten.');
            }
            $seen[$key] = true;
            foreach (['id', 'item_id', 'unit_id', 'base_unit_id', 'quantity', 'conversion_ratio', 'unit_price', 'subtotal', 'discount_amount', 'tax_amount'] as $field) {
                if (($line['snapshot'][$field] ?? null) !== $original[$field]) {
                    throw new InvalidArgumentException('Snapshot satuan atau nilai retur berbeda dari invoice asal.');
                }
            }
            $quantity = ReturnQuantity::from($line['quantity'], $original['quantity'], $original['conversion_ratio']);
            if ($quantity->baseQuantity !== $line['base_quantity']) {
                throw new InvalidArgumentException('Kuantitas dasar retur tidak sesuai sumber.');
            }
            $settled = $prior[$key] ?? ['quantity' => '0', 'amount' => '0'];
            $allocation = RefundAllocation::calculate($quantity->quantity, $amounts['quantity'], $amounts['net_amount'], $settled['quantity'], $settled['amount']);
            $lines[] = ['sales_return_line_id' => $line['id'], 'invoice_line_id' => $key, 'quantity' => $allocation->quantity, 'amount' => $allocation->amount];
            $total = $total->plus($allocation->amount);
        }
        usort($lines, fn ($a, $b) => strcmp($a['invoice_line_id'], $b['invoice_line_id']));
        $quote = ['id' => $record['id'], 'version' => $record['version'], 'invoice_revision' => $revision, 'total' => (string) $total, 'lines' => $lines];
        $basisLines = $basis->lines;
        ksort($basisLines);
        $fingerprint = hash('sha256', json_encode(['invoice_id' => $record['invoice_id'], 'basis' => ['total' => $basis->total, 'lines' => $basisLines], 'quote' => $quote], JSON_THROW_ON_ERROR));

        return $quote + ['quote_fingerprint' => $fingerprint];
    }
}
