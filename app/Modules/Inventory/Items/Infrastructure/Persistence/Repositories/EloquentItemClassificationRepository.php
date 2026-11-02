<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Items\Application\Contracts\ItemClassificationRepository;
use App\Modules\Inventory\Items\Application\Enums\ClassificationKind;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemBrand;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemCategory;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class EloquentItemClassificationRepository implements ItemClassificationRepository
{
    public function __construct(private TablePagination $pagination) {}

    private function query(ClassificationKind $kind): Builder
    {
        return match ($kind) {
            ClassificationKind::Category => ItemCategory::query(), ClassificationKind::Brand => ItemBrand::query()
        };
    }

    public function paginate(ClassificationKind $kind, int $page, array $filters): array
    {
        $query = $this->query($kind);
        if ($filters['trashed'] ?? false) {
            $query->onlyTrashed();
        }
        $query->when($filters['search'] ?? '', fn ($q, $search) => $q->where(fn ($q) => $q->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));
        if (in_array($filters['status'] ?? 'all', ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $rows = $query->orderBy('code')->orderBy('id')->paginate($this->pagination->perPage(), ['id', 'code', 'name', 'is_active', 'deleted_at'], page: $page);

        return ['data' => $rows->items(), 'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'per_page' => $rows->perPage(), 'total' => $rows->total()]];
    }

    public function choices(): array
    {
        $result = [];
        foreach (ClassificationKind::cases() as $kind) {
            $result[$kind->value] = $this->query($kind)->where('is_active', true)->orderBy('code')->orderBy('id')->limit(1000)->get(['id', 'code', 'name'])->toArray();
        }

        return $result;
    }

    public function lock(ClassificationKind $kind, string $id, bool $deleted = false): array
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Lock master memerlukan transaction.');
        }
        $query = $this->query($kind);

        return ($deleted ? $query->onlyTrashed() : $query)->whereKey($id)->lockForUpdate()->firstOrFail()->attributesToArray();
    }

    public function active(ClassificationKind $kind, string $id): bool
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Validasi master memerlukan transaction.');
        }

        return $this->query($kind)->whereKey($id)->where('is_active', true)->lockForUpdate()->first(['id']) !== null;
    }

    public function referenced(ClassificationKind $kind, string $id): bool
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Guard referensi memerlukan transaction.');
        }

        // Current locking read, not a repeatable-read snapshot created before the master lock.
        return Item::withTrashed()->where($kind->field(), $id)->lockForUpdate()->first(['id']) !== null;
    }

    public function save(ClassificationKind $kind, array $data, ?string $id = null): string
    {
        if ($id === null) {
            return $this->query($kind)->create($data)->id;
        }
        $this->query($kind)->findOrFail($id)->update($data);

        return $id;
    }

    public function delete(ClassificationKind $kind, string $id): void
    {
        $this->query($kind)->findOrFail($id)->delete();
    }

    public function restore(ClassificationKind $kind, string $id): void
    {
        $this->query($kind)->onlyTrashed()->findOrFail($id)->restore();
    }
}
