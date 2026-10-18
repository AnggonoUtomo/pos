<?php

namespace App\Modules\Finance\Payments\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\Contracts\RefundRepository;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\SaleRefundPaymentRequest;
use App\Modules\Finance\Payments\Application\DTOs\SaleRefundPaymentResult;
use App\Modules\Finance\Payments\Domain\Enums\PaymentMethodType;
use App\Modules\Finance\Payments\Domain\ValueObjects\RefundSettlement;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use LogicException;

class RecordSaleRefundPayment implements SaleRefundPaymentWriter
{
    public function __construct(private PaymentsRepository $masters, private RefundRepository $ledger) {}

    public function pay(SaleRefundPaymentRequest $request): SaleRefundPaymentResult
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Refund writer memerlukan transaction caller.');
        }
        $money = ['required', 'string', 'regex:/^[0-9]{1,28}(?:\.[0-9]{1,2})?$/D'];
        $input = Validator::make(['source_id' => $request->sourceId, 'source_number' => trim($request->sourceNumber), 'total' => $request->total, 'payments' => $request->payments, 'actor_id' => $request->actorId], [
            'source_id' => ['required', 'ulid'], 'source_number' => ['required', 'string', 'max:32'], 'total' => $money,
            'payments' => ['required', 'array', 'list', 'min:1', 'max:10'], 'payments.*' => ['required', 'array:method_id,amount'],
            'payments.*.method_id' => ['required', 'ulid'], 'payments.*.amount' => $money, 'actor_id' => ['nullable', 'integer', 'min:1'],
        ])->validate();
        $rows = [];
        foreach ($input['payments'] as $row) {
            $id = strtolower($row['method_id']);
            if (isset($rows[$id])) {
                throw new InvalidArgumentException('Metode refund harus unik.');
            }
            $rows[$id] = ['method_id' => $id, 'amount' => (string) BigDecimal::of($row['amount'])->toScale(2)];
        }
        ksort($rows);
        $data = ['source_id' => strtolower($input['source_id']), 'source_number' => $input['source_number'],
            'total' => (string) BigDecimal::of($input['total'])->toScale(2), 'payments' => array_values($rows)];
        RefundSettlement::assertExact($data['total'], array_column($rows, 'amount'));
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $result = function (array $existing) use ($hash): SaleRefundPaymentResult {
            if (! hash_equals($existing['request_hash'], $hash)) {
                throw new InvalidArgumentException('Retur sudah dibayar dengan isi berbeda.');
            }

            return new SaleRefundPaymentResult($existing['id'], $existing['paid_at'], $existing['snapshot']);
        };
        if (($existing = $this->ledger->findSource($data['source_id'])) !== null) {
            return $result($existing);
        }
        try {
            $assignments = [];
            foreach ($rows as $id => $row) {
                $assignments[$id] = $this->masters->methodAccountId($id);
            }
            $ids = array_values(array_unique($assignments));
            sort($ids);
            $accounts = $methods = [];
            foreach ($ids as $id) {
                $accounts[$id] = $this->masters->lockAccount($id);
            }
            foreach ($assignments as $id => $accountId) {
                $method = $this->masters->lockMethod($id);
                if ($method['payment_account_id'] !== $accountId) {
                    throw new InvalidArgumentException('Relasi metode refund berubah. Muat ulang halaman.');
                }
                $methods[$id] = $method;
            }
            if (($existing = $this->ledger->findSource($data['source_id'], lock: true)) !== null) {
                return $result($existing);
            }
            $lines = [];
            foreach ($rows as $id => $row) {
                $method = $methods[$id];
                $account = $accounts[$method['payment_account_id']];
                $type = PaymentMethodType::tryFrom($method['type']);
                if (! $method['is_active'] || ! $account['is_active'] || $type === null || $type->accountType()->value !== $account['type']) {
                    throw new InvalidArgumentException('Metode dan akun refund harus aktif dengan jenis yang sesuai.');
                }
                $lines[] = $row + ['account_id' => $account['id'], 'method_code' => $method['code'], 'method_name' => $method['name'], 'method_type' => $method['type'],
                    'account_code' => $account['code'], 'account_name' => $account['name'], 'account_type' => $account['type']];
            }
            $id = $this->ledger->create(['source_type' => 'sale_return', 'source_id' => $data['source_id'], 'source_number' => $data['source_number'],
                'request_hash' => $hash, 'total' => $data['total'], 'paid_at' => now()->startOfSecond(), 'paid_by' => $request->actorId], $lines);
            $this->ledger->audit($id, $request->actorId);

            return $result($this->ledger->findSource($data['source_id'], lock: true));
        } catch (ModelNotFoundException) {
            throw new InvalidArgumentException('Metode atau akun refund tidak tersedia.');
        } catch (UniqueConstraintViolationException $error) {
            throw new InvalidArgumentException('Sumber refund sudah digunakan. Muat ulang halaman.', previous: $error);
        }
    }
}
