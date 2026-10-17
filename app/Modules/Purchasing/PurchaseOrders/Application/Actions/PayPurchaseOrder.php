<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\PurchasePaymentRequest;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseOrderRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class PayPurchaseOrder
{
    public function __construct(private PurchaseOrderRepository $orders, private PurchasePaymentWriter $payments) {}

    public function handle(string $id, mixed $version, array $payments, ?int $actorId = null): array
    {
        Validator::make(['version' => $version], ['version' => ['required', 'integer', 'min:1', 'max:2147483646']])->validate();
        try {
            return DB::transaction(function () use ($id, $version, $payments, $actorId): array {
                $order = $this->orders->lock(strtolower($id));
                if ($order['status'] !== 'posted' || $order['stock_receipt_id'] === null) {
                    throw ValidationException::withMessages(['purchase_order' => 'Pembayaran hanya untuk pembelian yang sudah diterima.']);
                }
                $paid = $order['payment_disbursement_id'] !== null;
                if (($paid ? $order['paid_from_version'] : $order['version']) !== (int) $version) {
                    throw ValidationException::withMessages(['purchase_order' => 'Revisi pembayaran berbeda. Muat ulang halaman.']);
                }
                if ($paid && ($order['payment_snapshot'] === null || $order['paid_at'] === null)) {
                    throw new RuntimeException('Metadata pembayaran tidak lengkap.');
                }
                $result = $this->payments->pay(new PurchasePaymentRequest($order['id'], $order['number'], $order['total_value'], $payments, $actorId));
                if ($paid) {
                    if ($order['payment_disbursement_id'] !== $result->id || Arr::sortRecursive($order['payment_snapshot']) !== Arr::sortRecursive($result->snapshot)) {
                        throw new RuntimeException('Snapshot pembayaran tidak konsisten.');
                    }
                } else {
                    $this->orders->pay($order['id'], $result->id, $result->paidAt, $result->snapshot, (int) $version, $actorId);
                }

                return Arr::only($this->orders->lock($order['id']), ['id', 'status', 'version', 'payment_disbursement_id', 'payment_snapshot', 'paid_at']);
            }, 3);
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['purchase_order' => $error->getMessage()]);
        }
    }
}
