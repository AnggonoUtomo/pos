<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Repositories;

use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use App\Modules\Sales\POS\Application\Contracts\CashierShiftRepository;
use App\Modules\Sales\POS\Infrastructure\Persistence\Models\CashierShift;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseCashierShiftRepository implements CashierShiftRepository
{
    public function __construct(private TablePagination $pagination) {}

    public function lockActor(int $id): array
    {
        return (array) DB::table('users')->where('id', $id)->lockForUpdate()->firstOrFail(['id', 'name', 'is_active']);
    }

    private function row(?CashierShift $row): ?array
    {
        if ($row === null) {
            return null;
        }
        $data = $row->attributesToArray();
        foreach (['opening_balance', 'expected_cash', 'counted_cash', 'difference'] as $key) {
            if ($data[$key] !== null) {
                $data[$key] = (string) BigDecimal::of($data[$key])->toScale(6);
            }
        }
        foreach (['opened_at', 'closed_at', 'reviewed_at'] as $key) {
            if ($data[$key] !== null) {
                $data[$key] = Carbon::parse($data[$key], config('app.timezone'))->utc()->toISOString();
            }
        }
        $data['summary'] = $data['summary'] === null ? null : json_decode($data['summary'], true, flags: JSON_THROW_ON_ERROR);
        $data['actor_id'] = $data['actor_id'] === null ? null : (int) $data['actor_id'];
        unset($data['open_hash'], $data['close_hash'], $data['active_actor_id'], $data['active_account_id']);

        return $data;
    }

    public function active(int $actorId, bool $lock = false): ?array
    {
        return $this->row(CashierShift::where('active_actor_id', $actorId)->when($lock, fn ($q) => $q->lockForUpdate())->first());
    }

    public function hasActiveShift(int $actorId): bool
    {
        return CashierShift::where('active_actor_id', $actorId)->exists();
    }

    public function occupied(string $accountId, bool $lock = false): bool
    {
        return CashierShift::where('active_account_id', $accountId)->when($lock, fn ($q) => $q->lockForUpdate())->first() !== null;
    }

    public function find(string $id, bool $lock = false): ?array
    {
        $model = CashierShift::whereKey($id)->when($lock, fn ($q) => $q->lockForUpdate())->first();

        return $this->row($model);
    }

    public function save(array $data, ?string $id = null): string
    {
        $row = $id === null ? new CashierShift : CashierShift::findOrFail($id);
        $row->fill($data);
        if (! $row->save()) {
            throw new \RuntimeException('Shift tidak tersimpan.');
        }

        return $row->id;
    }

    public function summary(string $id): array
    {
        $total = $cash = $noncash = BigDecimal::zero()->toScale(6);
        $count = 0;
        foreach (DB::table('sales_invoices')->where('cashier_shift_id', $id)->orderBy('id')->cursor(['total', 'payments']) as $invoice) {
            $count++;
            $total = $total->plus((string) $invoice->total);
            foreach (json_decode($invoice->payments, true, flags: JSON_THROW_ON_ERROR)['payments'] as $payment) {
                if ($payment['method_type'] === 'cash') {
                    $cash = $cash->plus($payment['applied_amount']);
                } else {
                    $noncash = $noncash->plus($payment['applied_amount']);
                }
            }
        }

        return ['invoice_count' => $count, 'sales_total' => (string) $total, 'cash_sales' => (string) $cash, 'noncash_sales' => (string) $noncash];
    }

    public function paginate(?int $actorId, int $page): array
    {
        $result = CashierShift::when($actorId !== null, fn ($q) => $q->where('actor_id', $actorId))->orderByDesc('opened_at')->orderByDesc('id')->paginate($this->pagination->perPage(), page: $page);

        return ['data' => $result->getCollection()->map(fn ($row) => $this->row($row))->all(), 'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => $result->perPage(), 'total' => $result->total()]];
    }

    public function audit(string $id, string $event, int $actor): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Audit shift memerlukan transaction.');
        }
        $row = CashierShift::findOrFail($id);
        $audit = activity('sales.pos.shifts')->performedOn($row)->causedBy($actor)->event($event)->withProperties(['account_id' => $row->account_id, 'review_status' => $row->review_status])->log('Operasi shift kasir');
        if ($audit === null || ! $audit->exists) {
            throw new \RuntimeException('Audit shift tidak tersimpan.');
        }
    }
}
