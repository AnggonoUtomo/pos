<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentAccount;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentMethod;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentReceipt;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentPaymentsRepository implements PaymentsRepository
{
    public function __construct(private TablePagination $pagination) {}

    public function lockAccount(string $id, bool $deleted = false): array
    {
        return ($deleted ? PaymentAccount::onlyTrashed() : PaymentAccount::query())->whereKey($id)->lockForUpdate()->firstOrFail()->attributesToArray();
    }

    public function lockMethod(string $id, bool $deleted = false): array
    {
        return ($deleted ? PaymentMethod::onlyTrashed() : PaymentMethod::query())->whereKey($id)->lockForUpdate()->firstOrFail()->attributesToArray();
    }

    public function methodAccountId(string $id, bool $deleted = false): string
    {
        return ($deleted ? PaymentMethod::onlyTrashed() : PaymentMethod::query())->findOrFail($id)->payment_account_id;
    }

    public function isAccountReferenced(string $id): bool
    {
        return PaymentMethod::withTrashed()->where('payment_account_id', $id)->lockForUpdate()->first(['id']) !== null
            || (Schema::hasTable('cashbook_openings') && DB::table('cashbook_openings')->where('account_id', $id)->lockForUpdate()->first(['id']) !== null)
            || DB::table('payment_records')->where('account_id', $id)->lockForUpdate()->first(['id']) !== null
            || DB::table('payment_disbursement_lines')->where('account_id', $id)->lockForUpdate()->first(['id']) !== null
            || (Schema::hasTable('payment_refund_lines') && DB::table('payment_refund_lines')->where('account_id', $id)->lockForUpdate()->first(['id']) !== null)
            || (Schema::hasTable('purchase_refund_receipt_lines') && DB::table('purchase_refund_receipt_lines')->where('account_id', $id)->lockForUpdate()->first(['id']) !== null);
    }

    public function hasMethodHistory(string $id): bool
    {
        $this->assertTransaction();

        return DB::table('payment_records')->where('method_id', $id)->lockForUpdate()->first(['id']) !== null
            || DB::table('payment_disbursement_lines')->where('method_id', $id)->lockForUpdate()->first(['id']) !== null
            || (Schema::hasTable('payment_refund_lines') && DB::table('payment_refund_lines')->where('method_id', $id)->lockForUpdate()->first(['id']) !== null)
            || (Schema::hasTable('purchase_refund_receipt_lines') && DB::table('purchase_refund_receipt_lines')->where('method_id', $id)->lockForUpdate()->first(['id']) !== null);
    }

    public function lockReceiptBySource(string $sourceId): ?array
    {
        $this->assertTransaction();
        $row = DB::table('payment_receipts')->where('source_type', 'sale')->where('source_id', $sourceId)->lockForUpdate()->first();
        if ($row === null) {
            return null;
        }
        $result = (array) $row;
        $result['posted_by'] = $row->posted_by === null ? null : (int) $row->posted_by;
        $result['posted_at'] = Carbon::parse($row->posted_at, config('app.timezone'))->utc()->toISOString();
        foreach (['total', 'tendered', 'change'] as $key) {
            $result[$key] = (string) BigDecimal::of((string) $result[$key])->toScale(2);
        }
        $result['payments'] = DB::table('payment_records')->where('receipt_id', $row->id)->orderBy('line_number')->lockForUpdate()->get()->map(function ($line): array {
            $line = (array) $line;
            foreach (['tendered_amount', 'applied_amount', 'change_amount'] as $key) {
                $line[$key] = (string) BigDecimal::of((string) $line[$key])->toScale(2);
            }

            return $line;
        })->all();

        return $result;
    }

    public function createReceipt(array $header, array $lines): string
    {
        $this->assertTransaction();
        $receipt = PaymentReceipt::create($header);
        if (! $receipt->exists) {
            throw new \RuntimeException('Penerimaan pembayaran tidak tersimpan.');
        }
        foreach ($lines as $line) {
            DB::table('payment_records')->insert($line + ['id' => strtolower((string) Str::ulid()), 'receipt_id' => $receipt->id]);
        }

        return $receipt->id;
    }

    public function auditReceipt(string $id, ?int $actorId): void
    {
        $this->assertTransaction();
        $receipt = PaymentReceipt::findOrFail($id);
        $logger = activity('finance.payments')->performedOn($receipt)->event('received')
            ->withProperties(['source_type' => $receipt->source_type, 'source_id' => $receipt->source_id, 'number' => $receipt->source_number]);
        if ($actorId === null) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($actorId);
        }
        $audit = $logger->log('Penerimaan pembayaran penjualan');
        if ($audit === null || ! $audit->exists) {
            throw new \RuntimeException('Audit penerimaan pembayaran tidak tersimpan.');
        }
    }

    private function assertTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Pencatatan/locking pembayaran memerlukan transaction default.');
        }
    }

    public function saveAccount(array $data, ?string $id = null): string
    {
        $row = $id === null ? new PaymentAccount : PaymentAccount::whereKey($id)->lockForUpdate()->firstOrFail();
        $row->fill($data)->save();

        return $row->id;
    }

    public function saveMethod(array $data, ?string $id = null): string
    {
        $row = $id === null ? new PaymentMethod : PaymentMethod::whereKey($id)->lockForUpdate()->firstOrFail();
        $row->fill($data)->save();

        return $row->id;
    }

    public function deleteAccount(string $id): void
    {
        PaymentAccount::whereKey($id)->lockForUpdate()->firstOrFail()->delete();
    }

    public function restoreAccount(string $id): void
    {
        PaymentAccount::onlyTrashed()->whereKey($id)->lockForUpdate()->firstOrFail()->restore();
    }

    public function deleteMethod(string $id): void
    {
        PaymentMethod::whereKey($id)->lockForUpdate()->firstOrFail()->delete();
    }

    public function restoreMethod(string $id): void
    {
        PaymentMethod::onlyTrashed()->whereKey($id)->lockForUpdate()->firstOrFail()->restore();
    }

    public function paginateAccounts(int $page, array $filters): array
    {
        $query = ! empty($filters['trashed']) ? PaymentAccount::onlyTrashed() : PaymentAccount::query();

        return $this->paginate($query, $page, $filters);
    }

    public function paginateMethods(int $page, array $filters): array
    {
        $query = ! empty($filters['trashed']) ? PaymentMethod::onlyTrashed() : PaymentMethod::query();
        $query->with('account:id,code,name,type')->when($filters['payment_account_id'] ?? null, fn ($q, $id) => $q->where('payment_account_id', $id));
        $result = $this->paginate($query, $page, $filters);
        foreach ($result['data'] as &$row) {
            $row['account_name'] = $row['account']['name'];
            $row['account_type'] = $row['account']['type'];
            unset($row['account']);
        }
        unset($row);

        return $result;
    }

    private function paginate(Builder $query, int $page, array $filters): array
    {
        $query->when($filters['search'] ?? '', fn ($q, $search) => $q->where(fn ($r) => $r->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));
        $query->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type));
        if (in_array($filters['status'] ?? 'all', ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $rows = $query->orderBy('code')->orderBy('id')->paginate($this->pagination->perPage(), ['*'], page: $page);

        return ['data' => $rows->getCollection()->toArray(), 'meta' => ['current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(), 'per_page' => $rows->perPage(), 'total' => $rows->total()]];
    }

    public function accountChoices(): array
    {
        return PaymentAccount::where('is_active', true)->orderBy('code')->limit(1000)->get(['id', 'code', 'name', 'type'])->toArray();
    }

    public function findAccountByCode(string $code): ?array
    {
        return PaymentAccount::withTrashed()->where('code', $code)->first()?->attributesToArray();
    }

    public function methodCodeExists(string $code): bool
    {
        return PaymentMethod::withTrashed()->where('code', $code)->exists();
    }
}
