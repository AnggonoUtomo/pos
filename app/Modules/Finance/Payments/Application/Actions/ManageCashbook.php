<?php

namespace App\Modules\Finance\Payments\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\CashbookRepository;
use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Domain\ValueObjects\CashbookAmount;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageCashbook
{
    public function __construct(private CashbookRepository $book, private PaymentsRepository $accounts) {}

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function amount(string $value, bool $opening = false): string
    {
        try {
            return CashbookAmount::canonical($value, $opening);
        } catch (\InvalidArgumentException $error) {
            $this->fail('amount', $error->getMessage());
        }
    }

    private function date(string $value): Carbon
    {
        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, 'Asia/Jakarta');
        } catch (\Throwable) {
            $this->fail('occurred_on', 'Tanggal tidak valid.');
        }
        if (! $date || $date->format('Y-m-d') !== $value || $value < '1900-01-01' || $value > now('Asia/Jakarta')->toDateString()) {
            $this->fail('occurred_on', 'Tanggal harus valid dan tidak melebihi hari ini WIB.');
        }

        return $date;
    }

    private function lockAccounts(array $ids, ?string $date = null): array
    {
        sort($ids, SORT_STRING);
        $result = [];
        foreach (array_unique($ids) as $id) {
            $account = $this->accounts->lockAccount($id);
            if (! $account['is_active']) {
                $this->fail('account_id', 'Akun harus aktif.');
            }
            if ($date !== null) {
                $opening = $this->book->opening($id, lock: true);
                if ($opening === null || $date < $opening['starts_on']) {
                    $this->fail('occurred_on', 'Inisialisasi kedua akun dan gunakan tanggal sejak awal Buku Kas.');
                }
            }
            $result[$id] = $account;
        }

        return $result;
    }

    public function open(array $input, int $actor): string
    {
        $amount = $this->amount($input['amount'], opening: true);
        $this->date($input['starts_on']);

        return DB::transaction(function () use ($input, $amount, $actor): string {
            $this->lockAccounts([$input['account_id']]);
            if ($existing = $this->book->opening($input['account_id'], lock: true)) {
                if ($existing['starts_on'] !== $input['starts_on'] || ! BigDecimal::of($existing['amount'])->isEqualTo($amount)) {
                    $this->fail('account_id', 'Saldo awal sudah ditetapkan dan tidak dapat diedit.');
                }

                return $existing['id'];
            }
            $id = $this->book->createOpening(['account_id' => $input['account_id'], 'amount' => $amount, 'starts_on' => $input['starts_on'], 'created_by' => $actor, 'created_at' => now()]);
            $this->book->audit('opening', $id, $actor);

            return $id;
        }, 3);
    }

    public function post(array $input, int $actor): string
    {
        $amount = $this->amount($input['amount']);
        $date = $this->date($input['occurred_on']);
        if (! in_array($input['kind'], ['incoming', 'outgoing', 'transfer'], true)) {
            $this->fail('kind', 'Jenis mutasi tidak valid.');
        }
        $counter = $input['counter_account_id'] ?? null;
        if (($input['kind'] === 'transfer') !== ($counter !== null) || $counter === $input['account_id']) {
            $this->fail('counter_account_id', 'Transfer memerlukan dua akun berbeda; mutasi lain tidak memakai akun tujuan.');
        }
        $description = trim($input['description']);
        if ($description === '' || mb_strlen($description) > 200) {
            $this->fail('description', 'Isi keterangan maksimal 200 karakter.');
        }
        $data = ['kind' => $input['kind'], 'account_id' => $input['account_id'], 'counter_account_id' => $counter, 'amount' => $amount, 'occurred_on' => $input['occurred_on'], 'description' => $description, 'posted_by' => $actor];
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($input, $data, $date, $hash, $actor): string {
            $accounts = $this->lockAccounts(array_filter([$data['account_id'], $data['counter_account_id']]), $input['occurred_on']);
            if ($existing = $this->book->entryByKey($input['idempotency_key'])) {
                abort_unless(hash_equals($existing['request_hash'], $hash), 409, 'Kunci pengiriman sudah dipakai untuk mutasi berbeda.');

                return $existing['id'];
            }
            $row = $data;
            unset($row['occurred_on']);
            foreach (['account' => $data['account_id'], 'counter_account' => $data['counter_account_id']] as $prefix => $accountId) {
                foreach (['code', 'name', 'type'] as $key) {
                    $row[$prefix.'_'.$key] = $accountId ? $accounts[$accountId][$key] : null;
                }
            }
            $row += ['idempotency_key' => $input['idempotency_key'], 'request_hash' => $hash, 'direction' => $data['kind'] === 'incoming' ? 'incoming' : 'outgoing',
                'occurred_at' => $date->setTimeFromTimeString(now('Asia/Jakarta')->format('H:i:s'))->setTimezone(config('app.timezone')), 'posted_at' => now()];
            $id = $this->book->createEntry($row);
            $this->book->audit('posted', $id, $actor);

            return $id;
        }, 3);
    }

    public function reverse(string $id, string $reason, int $actor): string
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 200) {
            $this->fail('reason', 'Alasan pembalikan wajib diisi maksimal 200 karakter.');
        }
        $source = $this->book->entry($id);
        abort_if($source === null, 404);

        return DB::transaction(function () use ($id, $source, $reason, $actor): string {
            $this->lockAccounts(array_filter([$source['account_id'], $source['counter_account_id']]), now('Asia/Jakarta')->toDateString());
            $source = $this->book->entry($id, lock: true);
            if ($source['kind'] === 'reversal') {
                $this->fail('reason', 'Pembalik tidak dapat dibalik kembali.');
            }
            if ($existing = $this->book->entryByKey('reverse:'.$id)) {
                abort_unless($existing['description'] === $reason, 409, 'Mutasi sudah dibalik dengan alasan berbeda.');

                return $existing['id'];
            }
            unset($source['id'], $source['idempotency_key'], $source['request_hash']);
            $source = array_replace($source, ['kind' => 'reversal', 'direction' => $source['direction'] === 'incoming' ? 'outgoing' : 'incoming',
                'idempotency_key' => 'reverse:'.$id, 'request_hash' => hash('sha256', $id.'|'.$reason), 'description' => $reason,
                'reversed_entry_id' => $id, 'posted_at' => now(), 'occurred_at' => now(), 'posted_by' => $actor]);
            $saved = $this->book->createEntry($source);
            $this->book->audit('reversed', $saved, $actor);

            return $saved;
        }, 3);
    }
}
