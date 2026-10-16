<?php

namespace App\Modules\Finance\Payments\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\Contracts\SalePaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\SalePaymentRequest;
use App\Modules\Finance\Payments\Application\DTOs\SalePaymentResult;
use App\Modules\Finance\Payments\Domain\Enums\PaymentMethodType;
use App\Modules\Finance\Payments\Domain\ValueObjects\PaymentSettlement;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class RecordSalePayment implements SalePaymentWriter
{
    public function __construct(private PaymentsRepository $repository) {}

    public function receive(SalePaymentRequest $request): SalePaymentResult
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Sale payment writer memerlukan transaction caller.');
        }
        $result = $this->handle(['source_id' => $request->sourceId, 'source_number' => $request->sourceNumber,
            'total' => $request->total, 'payments' => $request->payments], $request->actorId);
        $rows = array_map(fn ($row) => array_intersect_key($row, array_flip(['method_id', 'method_name', 'method_type',
            'tendered_amount', 'applied_amount', 'change_amount'])), $result['payments']);

        return new SalePaymentResult($result['id'], ['total' => $result['total'], 'tendered' => $result['tendered'],
            'change' => $result['change'], 'payments' => $rows]);
    }

    public function handle(array $input, ?int $actorId = null): array
    {
        $input = $this->normalize($input);
        if ($actorId !== null && $actorId < 1) {
            throw new InvalidArgumentException('Actor pembayaran tidak valid.');
        }
        $hash = hash('sha256', json_encode([$input, $actorId], JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($input, $actorId, $hash): array {
                // Discovery is not authoritative: recheck each assignment under its method lock.
                $assignments = [];
                foreach ($input['payments'] as $row) {
                    $assignments[$row['method_id']] = $this->repository->methodAccountId($row['method_id']);
                }
                $accountIds = array_values(array_unique($assignments));
                sort($accountIds);
                $accounts = $methods = [];
                foreach ($accountIds as $id) {
                    $accounts[$id] = $this->repository->lockAccount($id);
                }
                foreach ($assignments as $id => $accountId) {
                    $method = $this->repository->lockMethod($id);
                    if ($method['payment_account_id'] !== $accountId) {
                        throw new InvalidArgumentException('Relasi akun pembayaran berubah. Muat ulang transaksi.');
                    }
                    $methods[$id] = $method;
                }
                $existing = $this->repository->lockReceiptBySource($input['source_id']);
                if ($existing !== null) {
                    if (! hash_equals($existing['request_hash'], $hash)) {
                        throw new InvalidArgumentException('Sumber pembayaran sudah digunakan dengan isi atau actor berbeda.');
                    }

                    return $existing;
                }
                $tenders = [];
                foreach ($input['payments'] as $row) {
                    $method = $methods[$row['method_id']];
                    $account = $accounts[$method['payment_account_id']];
                    $type = PaymentMethodType::tryFrom($method['type']);
                    if (! $method['is_active'] || ! $account['is_active'] || $type === null || $type->accountType()->value !== $account['type']) {
                        throw new InvalidArgumentException('Metode dan akun pembayaran harus aktif dengan jenis yang sesuai.');
                    }
                    $tenders[] = $row + ['type' => $type->value];
                }
                $settlement = PaymentSettlement::calculate($input['total'], $tenders);
                $lines = [];
                foreach ($settlement->payments as $index => $row) {
                    $method = $methods[$row['method_id']];
                    $account = $accounts[$method['payment_account_id']];
                    $lines[] = ['line_number' => $index + 1, 'method_id' => $method['id'], 'account_id' => $account['id'],
                        'method_code' => $method['code'], 'method_name' => $method['name'], 'method_type' => $method['type'],
                        'account_code' => $account['code'], 'account_name' => $account['name'], 'account_type' => $account['type'],
                        'tendered_amount' => $row['tendered_amount'], 'applied_amount' => $row['applied_amount'], 'change_amount' => $row['change_amount']];
                }
                $id = $this->repository->createReceipt(['source_type' => 'sale', 'source_id' => $input['source_id'],
                    'source_number' => $input['source_number'], 'request_hash' => $hash, 'total' => $settlement->total,
                    'tendered' => $settlement->tendered, 'change' => $settlement->change, 'posted_at' => now(), 'posted_by' => $actorId], $lines);
                $this->repository->auditReceipt($id, $actorId);

                return $this->repository->lockReceiptBySource($input['source_id']);
            }, attempts: 3);
        } catch (ModelNotFoundException) {
            throw new InvalidArgumentException('Metode atau akun pembayaran tidak tersedia.');
        } catch (UniqueConstraintViolationException $exception) {
            throw new InvalidArgumentException('Sumber pembayaran sudah digunakan. Muat ulang transaksi.', previous: $exception);
        }
    }

    private function normalize(array $input): array
    {
        $money = ['required', 'string', 'regex:/^\d{1,28}(?:\.\d{1,2})?$/D'];
        $data = Validator::make($input, ['source_id' => ['required', 'ulid'], 'source_number' => ['required', 'string', 'max:32'],
            'total' => $money, 'payments' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'payments.*' => ['required', 'array:method_id,amount'], 'payments.*.method_id' => ['required', 'ulid'], 'payments.*.amount' => $money])->validate();
        $rows = [];
        foreach ($data['payments'] as $row) {
            $id = strtolower($row['method_id']);
            if (isset($rows[$id])) {
                throw new InvalidArgumentException('Metode pembayaran harus unik.');
            }
            $rows[$id] = ['method_id' => $id, 'amount' => (string) BigDecimal::of($row['amount'])->toScale(2)];
        }
        ksort($rows);

        return ['source_id' => strtolower($data['source_id']), 'source_number' => trim($data['source_number']),
            'total' => (string) BigDecimal::of($data['total'])->toScale(2), 'payments' => array_values($rows)];
    }
}
