<?php

namespace App\Modules\Purchasing\Returns\Application\Queries;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnRefundRepository;
use App\Modules\Purchasing\Returns\Domain\ValueObjects\RefundAllocation;
use App\Modules\Purchasing\Returns\Domain\ValueObjects\RefundPurchaseBasis;
use App\Modules\Purchasing\Returns\Domain\ValueObjects\ReturnQuantity;
use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class ValueReturnRefund
{
    public function __construct(private ReturnPurchaseLookup $purchases, private ReturnRefundRepository $refunds) {}

    public function handle(array $record, ?array $scope, bool $lock = false): array
    {
        if ($record['status'] !== 'posted' || $record['stock_issue_id'] === null || $record['posted_at'] === null
            || $record['posted_from_version'] === null || $record['lines'] === [] || count($record['lines']) > 100) {
            throw new InvalidArgumentException('Hanya retur yang sudah diserahkan lengkap yang dapat diselesaikan.');
        }
        $revision = $scope['revision'] ?? 0;
        if ($revision < 0 || $revision >= 2147483647) {
            throw new InvalidArgumentException('Revisi refund pembelian telah mencapai batas.');
        }
        $invoice = $this->purchases->findEligible($record['purchase_order_id']) ?? throw new InvalidArgumentException('Pembelian asal tidak tersedia.');
        $summary = $this->purchases->refundBasis($record['purchase_order_id']) ?? throw new InvalidArgumentException('Nilai pembelian asal tidak tersedia.');
        $basis = RefundPurchaseBasis::fromSnapshot($summary->header, $summary->lines);
        if ($summary->header['id'] !== $record['purchase_order_id'] || ($record['snapshot']['warehouse_id'] ?? null) !== $invoice->header['warehouse_id']) {
            throw new InvalidArgumentException('Snapshot gudang atau pembelian retur tidak sesuai sumber.');
        }
        $source = array_column($invoice->lines, null, 'id');
        $prior = $scope === null ? [] : $this->refunds->allocations($scope['id'], $lock);
        $lines = [];
        $total = BigDecimal::zero()->toScale(6);
        $seen = [];
        foreach ($record['lines'] as $line) {
            $key = $line['purchase_order_line_id'];
            $original = $source[$key] ?? throw new InvalidArgumentException('Baris pembelian asal tidak tersedia.');
            $amounts = $basis->lines[$key] ?? throw new InvalidArgumentException('Nilai baris pembelian asal tidak tersedia.');
            if (isset($seen[$key]) || ! is_array($line['snapshot']) || $amounts['quantity'] !== $original['quantity']) {
                throw new InvalidArgumentException('Snapshot baris retur tidak konsisten.');
            }
            $seen[$key] = true;
            foreach (['id', 'item_id', 'unit_id', 'base_unit_id', 'quantity', 'base_quantity', 'conversion_ratio', 'unit_cost', 'total_value'] as $field) {
                if (($line['snapshot'][$field] ?? null) !== $original[$field]) {
                    throw new InvalidArgumentException('Snapshot satuan atau nilai retur berbeda dari pembelian asal.');
                }
            }
            $quantity = ReturnQuantity::from($line['quantity'], $original['quantity'], $original['conversion_ratio']);
            if ($quantity->baseQuantity !== $line['base_quantity']) {
                throw new InvalidArgumentException('Kuantitas dasar retur tidak sesuai sumber.');
            }
            $settled = $prior[$key] ?? ['quantity' => '0', 'amount' => '0'];
            $allocation = RefundAllocation::calculate($quantity->quantity, $amounts['quantity'], $amounts['amount'], $settled['quantity'], $settled['amount']);
            $lines[] = ['purchase_return_line_id' => $line['id'], 'purchase_order_line_id' => $key, 'quantity' => $allocation->quantity, 'amount' => $allocation->amount];
            $total = $total->plus($allocation->amount);
        }
        usort($lines, fn ($a, $b) => strcmp($a['purchase_order_line_id'], $b['purchase_order_line_id']));
        $quote = ['id' => $record['id'], 'version' => $record['version'], 'purchase_revision' => $revision,
            'purchase_order_id' => $record['purchase_order_id'], 'payment_disbursement_id' => $summary->header['payment_disbursement_id'],
            'total' => (string) $total, 'lines' => $lines];
        $basisLines = $basis->lines;
        ksort($basisLines);
        $fingerprint = hash('sha256', json_encode(['purchase_order_id' => $record['purchase_order_id'], 'basis' => ['total' => $basis->total, 'lines' => $basisLines], 'quote' => $quote], JSON_THROW_ON_ERROR));

        return $quote + ['quote_fingerprint' => $fingerprint];
    }
}
