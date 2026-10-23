<?php

namespace App\Modules\Purchasing\Returns\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptWriter;
use App\Modules\Finance\Payments\Application\DTOs\PurchaseRefundReceiptRequest;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnRefundRepository;
use App\Modules\Purchasing\Returns\Application\Queries\ValueReturnRefund;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class SettleReturnRefund
{
    public function __construct(private ReturnRefundRepository $refunds, private ValueReturnRefund $value, private PurchaseRefundReceiptWriter $payments) {}

    public function handle(string $id, array $input, ?int $actorId = null): array
    {
        if (! $this->refunds->available()) {
            throw ValidationException::withMessages(['refund' => 'Refund belum diaktifkan pada database ini.']);
        }
        $input = $this->normalize($input, $actorId);
        $id = strtolower($id);
        $hash = hash('sha256', json_encode(['id' => $id] + $input, JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($id, $input, $hash, $actorId): array {
                $discovery = $this->refunds->findReturn($id);
                $scope = $this->refunds->scope($discovery['purchase_order_id'], lock: true) ?? throw new RuntimeException('Scope refund tidak tersimpan.');
                $record = $this->refunds->findReturn($id, lock: true);
                if ($record['purchase_order_id'] !== $discovery['purchase_order_id'] || $record['status'] !== 'posted' || $record['version'] !== $input['version']) {
                    throw ValidationException::withMessages(['version' => 'Retur belum diterima atau revisinya berubah.']);
                }
                if (($existing = $this->refunds->settlement($id, lock: true)) !== null) {
                    if (! hash_equals($existing['request_hash'], $hash)) {
                        throw new InvalidArgumentException('Retur sudah diselesaikan dengan permintaan berbeda.');
                    }

                    return $existing['snapshot'];
                }
                $quote = $this->value->handle($record, $scope, lock: true);
                if (! hash_equals($quote['quote_fingerprint'], $input['quote_fingerprint'])) {
                    throw ValidationException::withMessages(['quote_fingerprint' => 'Hak refund berubah. Muat ulang dan konfirmasi quote terbaru.']);
                }
                $payment = null;
                if ($quote['total'] === '0.000000') {
                    if ($input['payments'] !== []) {
                        throw new InvalidArgumentException('Penyelesaian tanpa uang tidak boleh memiliki metode pembayaran.');
                    }
                } else {
                    $payment = $this->payments->receive(new PurchaseRefundReceiptRequest($id, $record['number'],
                        $quote['purchase_order_id'], $quote['payment_disbursement_id'], $quote['total'], $input['payments'], $actorId));
                }
                $settlementId = strtolower((string) Str::ulid());
                $time = now()->startOfSecond();
                $outcome = $payment === null ? 'no_payment' : 'received';
                $result = ['id' => $id, 'status' => 'posted', 'version' => $record['version'], 'settlement' => [
                    'id' => $settlementId, 'outcome' => $outcome, 'total' => $quote['total'], 'finance_receipt_id' => $payment?->id,
                    'settled_at' => $time->copy()->utc()->toISOString(), 'payments' => $payment?->snapshot['payments'] ?? []]];
                $this->refunds->save(['id' => $settlementId, 'purchase_return_id' => $id, 'scope_id' => $scope['id'], 'outcome' => $outcome,
                    'total' => $quote['total'], 'finance_receipt_id' => $payment?->id, 'from_version' => $record['version'],
                    'scope_revision' => $scope['revision'], 'quote_fingerprint' => $quote['quote_fingerprint'], 'request_hash' => $hash,
                    'snapshot' => $result, 'settled_at' => $time, 'settled_by' => $actorId], $quote['lines']);
                $this->refunds->audit($settlementId, $actorId);

                return $this->refunds->settlement($id, lock: true)['snapshot'] ?? throw new RuntimeException('Hasil penyelesaian refund tidak tersedia.');
            }, 3);
        } catch (InvalidArgumentException|JsonException|MathException $error) {
            throw ValidationException::withMessages(['refund' => $error instanceof InvalidArgumentException ? $error->getMessage() : 'Snapshot retur atau pembelian asal tidak valid.']);
        }
    }

    private function normalize(array $input, ?int $actorId): array
    {
        if (array_diff(array_keys($input), ['version', 'quote_fingerprint', 'payments']) !== []) {
            throw ValidationException::withMessages(['refund' => 'Payload refund mengandung field yang tidak diizinkan.']);
        }
        $data = Validator::make($input + ['actor_id' => $actorId], [
            'version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'actor_id' => ['nullable', 'integer', 'min:1'],
            'quote_fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'payments' => ['present', 'array', 'list', 'max:10'],
            'payments.*' => ['required', 'array:method_id,amount'], 'payments.*.method_id' => ['required', 'ulid'],
            'payments.*.amount' => ['required', 'string', 'regex:/^[0-9]{1,24}(?:\.[0-9]{1,6})?$/D'],
        ])->validate();
        $rows = [];
        foreach ($data['payments'] as $row) {
            $key = strtolower($row['method_id']);
            if (isset($rows[$key]) || ! BigDecimal::of($row['amount'])->isPositive()) {
                throw ValidationException::withMessages(['payments' => 'Metode harus unik dan setiap pembayaran harus positif.']);
            }
            $rows[$key] = ['method_id' => $key, 'amount' => (string) BigDecimal::of($row['amount'])->toScale(6)];
        }
        ksort($rows);

        return ['version' => (int) $data['version'], 'quote_fingerprint' => $data['quote_fingerprint'], 'payments' => array_values($rows)];
    }
}
