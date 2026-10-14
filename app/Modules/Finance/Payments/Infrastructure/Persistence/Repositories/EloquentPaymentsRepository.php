<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentAccount;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;

class EloquentPaymentsRepository implements PaymentsRepository
{
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
        return PaymentMethod::withTrashed()->where('payment_account_id', $id)->lockForUpdate()->first(['id']) !== null;
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
        $rows = $query->orderBy('code')->orderBy('id')->paginate(25, ['*'], page: $page);

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
