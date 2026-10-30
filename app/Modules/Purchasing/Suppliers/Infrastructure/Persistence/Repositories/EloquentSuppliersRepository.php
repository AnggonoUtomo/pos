<?php

namespace App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Repositories;

use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SuppliersRepository;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

class EloquentSuppliersRepository implements SuppliersRepository
{
    public function __construct(private TablePagination $pagination) {}

    public function save(array $data, ?string $id = null): string
    {
        $this->requireTransaction();
        $model = $id === null ? new Supplier : Supplier::query()->lockForUpdate()->findOrFail($id);
        $old = $model->attributesToArray();
        $model->fill(Arr::only($data, ['code', 'name', 'phone', 'email', 'address', 'is_active']));
        if ($model->exists && ! $model->isDirty()) {
            return $model->id;
        }
        $changed = array_keys($model->getDirty());
        if (! $model->save()) {
            throw new RuntimeException('Supplier gagal disimpan.');
        }
        $this->audit($model, $id === null ? 'created' : 'updated', $old, $changed);

        return $model->id;
    }

    public function delete(string $id): void
    {
        $this->requireTransaction();
        $model = Supplier::query()->lockForUpdate()->findOrFail($id);
        $old = $model->attributesToArray();
        if (! $model->delete()) {
            throw new RuntimeException('Supplier gagal dihapus.');
        }
        $this->audit($model, 'deleted', $old, ['deleted_at']);
    }

    public function restore(string $id): void
    {
        $this->requireTransaction();
        $model = Supplier::onlyTrashed()->lockForUpdate()->findOrFail($id);
        $old = $model->attributesToArray();
        if (! $model->restore()) {
            throw new RuntimeException('Supplier gagal dipulihkan.');
        }
        $this->audit($model, 'restored', $old, ['deleted_at']);
    }

    private function requireTransaction(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Mutation supplier memerlukan transaction boundary.');
        }
    }

    private function audit(Supplier $model, string $event, array $old, array $changed): void
    {
        // Kontak tidak masuk audit; hanya nama field yang berubah.
        $public = ['code', 'name', 'is_active', 'deleted_at'];
        $log = activity('purchasing.suppliers')->performedOn($model)->event($event)
            ->withProperties(['attributes' => Arr::only($model->attributesToArray(), $public),
                'old' => Arr::only($old, $public), 'changed_fields' => $changed])
            ->log('Supplier '.$event);
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit supplier gagal disimpan.');
        }
    }

    public function paginate(int $page, array $filters): array
    {
        $query = ! empty($filters['trashed']) ? Supplier::onlyTrashed() : Supplier::query();
        if (($search = $filters['search'] ?? '') !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn ($q) => $q->whereRaw("code LIKE ? ESCAPE '!'", [$like])->orWhereRaw("name LIKE ? ESCAPE '!'", [$like]));
        }
        if (in_array($filters['status'] ?? 'all', ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $result = $query->orderBy('code')->orderBy('id')->paginate($this->pagination->perPage(),
            ['id', 'code', 'name', 'phone', 'email', 'address', 'is_active', 'deleted_at'], page: $page);

        return ['data' => $result->items(), 'meta' => ['current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(), 'per_page' => $result->perPage(), 'total' => $result->total()]];
    }

    public function codeExists(string $code): bool
    {
        return Supplier::withTrashed()->where('code', $code)->exists();
    }
}
