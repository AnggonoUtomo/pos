<?php

namespace App\Modules\Sales\Parties\Infrastructure\Persistence\Repositories;

use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use App\Modules\Sales\Parties\Application\Contracts\PartiesRepository;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\Customer;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\ItemPrice;
use Illuminate\Database\Eloquent\Builder;

class EloquentPartiesRepository implements PartiesRepository
{
    public function __construct(private TablePagination $pagination) {}

    public function lockLevel(string $id, bool $deleted = false): array
    {
        return ($deleted ? CustomerLevel::onlyTrashed() : CustomerLevel::query())->whereKey($id)->lockForUpdate()->firstOrFail()->attributesToArray();
    }

    public function lockCustomer(string $id, bool $deleted = false): array
    {
        return ($deleted ? Customer::onlyTrashed() : Customer::query())->whereKey($id)->lockForUpdate()->firstOrFail()->attributesToArray();
    }

    public function customerLevelId(string $id, bool $deleted = false): string
    {
        return ($deleted ? Customer::onlyTrashed() : Customer::query())->findOrFail($id)->customer_level_id;
    }

    public function isLevelReferenced(string $id): bool
    {
        // A current read sees committed assignments even after an earlier consistent read.
        return Customer::withTrashed()->where('customer_level_id', $id)->lockForUpdate()->first(['id']) !== null
            || ItemPrice::withTrashed()->where('customer_level_id', $id)->lockForUpdate()->first(['id']) !== null;
    }

    public function saveLevel(array $data, ?string $id = null): string
    {
        $model = $id === null ? new CustomerLevel : CustomerLevel::query()->lockForUpdate()->findOrFail($id);
        $model->fill($data)->save();

        return $model->id;
    }

    public function saveCustomer(array $data, ?string $id = null): string
    {
        $model = $id === null ? new Customer : Customer::query()->lockForUpdate()->findOrFail($id);
        $model->fill($data)->save();

        return $model->id;
    }

    public function deleteLevel(string $id): void
    {
        CustomerLevel::query()->lockForUpdate()->findOrFail($id)->delete();
    }

    public function restoreLevel(string $id): void
    {
        CustomerLevel::onlyTrashed()->lockForUpdate()->findOrFail($id)->restore();
    }

    public function deleteCustomer(string $id): void
    {
        Customer::query()->lockForUpdate()->findOrFail($id)->delete();
    }

    public function restoreCustomer(string $id): void
    {
        Customer::onlyTrashed()->lockForUpdate()->findOrFail($id)->restore();
    }

    public function paginateLevels(int $page, array $filters): array
    {
        $query = ! empty($filters['trashed']) ? CustomerLevel::onlyTrashed() : CustomerLevel::query();

        return $this->paginate($query, $page, $filters, ['id', 'code', 'name', 'is_active', 'deleted_at']);
    }

    public function paginateCustomers(int $page, array $filters): array
    {
        $query = ! empty($filters['trashed']) ? Customer::onlyTrashed() : Customer::query();
        $query->with('level:id,code,name');
        $query->when($filters['customer_level_id'] ?? null, fn ($q, $id) => $q->where('customer_level_id', $id));
        $result = $this->paginate($query, $page, $filters, ['id', 'code', 'name', 'customer_level_id', 'phone', 'email', 'address', 'is_active', 'deleted_at']);
        $result['data'] = array_map(function (array $row): array {
            $row['customer_level_name'] = $row['level']['name'];
            unset($row['level']);

            return $row;
        }, $result['data']);

        return $result;
    }

    private function paginate(Builder $query, int $page, array $filters, array $columns): array
    {
        $query->when($filters['search'] ?? '', fn ($q, $search) => $q->where(fn ($row) => $row
            ->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));
        if (in_array($filters['status'] ?? 'all', ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $paginator = $query->orderBy('code')->orderBy('id')->paginate($this->pagination->perPage(), $columns, page: $page);

        return ['data' => $paginator->getCollection()->toArray(), 'meta' => [
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
        ]];
    }

    public function levelChoices(): array
    {
        return CustomerLevel::where('is_active', true)->orderBy('code')->limit(1000)->get(['id', 'code', 'name'])->toArray();
    }

    public function findLevelByCode(string $code): ?array
    {
        return CustomerLevel::withTrashed()->where('code', $code)->first()?->attributesToArray();
    }

    public function customerCodeExists(string $code): bool
    {
        return Customer::withTrashed()->where('code', $code)->exists();
    }
}
