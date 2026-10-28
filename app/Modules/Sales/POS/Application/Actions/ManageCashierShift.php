<?php

namespace App\Modules\Sales\POS\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\CashDrawerPosition;
use App\Modules\Sales\POS\Application\Contracts\CashierShiftRepository;
use App\Modules\Sales\POS\Domain\ValueObjects\ShiftCashCount;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageCashierShift
{
    public function __construct(private CashierShiftRepository $shifts, private CashDrawerPosition $drawers) {}

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public function open(string $id, string $accountId, int $actor): array
    {
        return DB::transaction(function () use ($id, $accountId, $actor): array {
            $user = $this->shifts->lockActor($actor);
            if (! $user['is_active']) {
                $this->fail('shift', 'Akun kasir tidak aktif.');
            }
            if ($existing = $this->shifts->find($id, lock: true)) {
                abort_unless($existing['actor_id'] === $actor && $existing['account_id'] === $accountId, 409, 'Kunci shift sudah dipakai.');

                return $existing;
            }
            if ($this->shifts->active($actor, lock: true) || $this->shifts->occupied($accountId, lock: true)) {
                $this->fail('account_id', 'Kasir atau laci masih memiliki shift aktif.');
            }
            $position = $this->drawers->read($accountId, lock: true);
            if (BigDecimal::of($position->balance)->abs()->isGreaterThan('99999999999999999999999999999999.999999')) {
                $this->fail('account_id', 'Saldo laci melebihi batas shift.');
            }
            $this->shifts->save(['id' => $id, 'actor_id' => $actor, 'actor_name' => $user['name'], 'account_id' => $accountId, 'account_code' => $position->code, 'account_name' => $position->name,
                'active_actor_id' => $actor, 'active_account_id' => $accountId, 'status' => 'open', 'review_status' => 'not_required', 'open_hash' => hash('sha256', $actor.'|'.$accountId), 'opening_balance' => $position->balance, 'opened_at' => now()]);
            $this->shifts->audit($id, 'opened', $actor);

            return $this->shifts->find($id);
        }, 3);
    }

    public function checkout(int $actor, ?string $expectedId = null): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Pengaman shift memerlukan transaction caller.');
        }
        $this->shifts->lockActor($actor);
        $shift = $this->shifts->active($actor, lock: true);
        if ($shift === null || ($expectedId !== null && $shift['id'] !== $expectedId)) {
            $this->fail('shift_id', 'Buka shift aktif yang sesuai sebelum checkout.');
        }

        return $shift;
    }

    public function close(string $id, string $counted, string $reason, int $actor): array
    {
        try {
            $counted = ShiftCashCount::canonical($counted);
        } catch (\InvalidArgumentException $error) {
            $this->fail('counted', $error->getMessage());
        }
        $reason = trim($reason);
        if (mb_strlen($reason) > 200) {
            $this->fail('reason', 'Alasan maksimal 200 karakter.');
        }

        return DB::transaction(function () use ($id, $counted, $reason, $actor): array {
            $this->shifts->lockActor($actor);
            $shift = $this->shifts->find($id, lock: true);
            abort_if($shift === null, 404);
            abort_unless($shift['actor_id'] === $actor, 403, 'Shift hanya dapat ditutup oleh kasir pemilik.');
            if ($shift['status'] === 'closed') {
                abort_unless($shift['counted_cash'] === $counted && $shift['close_reason'] === $reason, 409, 'Shift sudah ditutup dengan hitungan berbeda.');

                return $shift;
            }
            $position = $this->drawers->read($shift['account_id'], lock: true);
            $difference = BigDecimal::of($counted)->minus($position->balance)->toScale(6);
            if ($difference->abs()->isGreaterThan('99999999999999999999999999999999.999999')) {
                $this->fail('counted', 'Selisih melebihi batas shift.');
            }
            if (! $difference->isZero() && $reason === '') {
                $this->fail('reason', 'Alasan wajib diisi jika ada selisih.');
            }
            $summary = $this->shifts->summary($id);
            $summary['other_cash_net'] = (string) BigDecimal::of($position->balance)->minus($shift['opening_balance'])->minus($summary['cash_sales'])->toScale(6);
            $this->shifts->save(['status' => 'closed', 'active_actor_id' => null, 'active_account_id' => null, 'expected_cash' => $position->balance, 'counted_cash' => $counted,
                'difference' => (string) $difference, 'close_reason' => $reason, 'close_hash' => hash('sha256', $counted.'|'.$reason), 'review_status' => $difference->isZero() ? 'not_required' : 'pending', 'summary' => json_encode($summary, JSON_THROW_ON_ERROR), 'closed_at' => now()], $id);
            $this->shifts->audit($id, 'closed', $actor);

            return $this->shifts->find($id);
        }, 3);
    }

    public function review(string $id, string $note, int $actor): array
    {
        $note = trim($note);
        if ($note === '' || mb_strlen($note) > 200) {
            $this->fail('note', 'Catatan review wajib, maksimal 200 karakter.');
        }

        return DB::transaction(function () use ($id, $note, $actor): array {
            $shift = $this->shifts->find($id, lock: true);
            abort_if($shift === null, 404);
            if ($shift['status'] !== 'closed' || $shift['review_status'] === 'not_required') {
                $this->fail('shift', 'Shift ini tidak menunggu review selisih.');
            }
            if ($shift['review_status'] === 'reviewed') {
                abort_unless($shift['review_note'] === $note, 409, 'Shift sudah direview.');

                return $shift;
            }
            $this->shifts->save(['review_status' => 'reviewed', 'review_note' => $note, 'reviewed_by' => $actor, 'reviewed_at' => now()], $id);
            $this->shifts->audit($id, 'reviewed', $actor);

            return $this->shifts->find($id);
        }, 3);
    }
}
