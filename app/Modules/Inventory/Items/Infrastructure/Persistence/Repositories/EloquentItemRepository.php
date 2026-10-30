<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\Contracts\ItemUnitUsage;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemUnit;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;

class EloquentItemRepository implements ItemRepository, ItemUnitUsage
{
    public function __construct(private TablePagination $pagination) {}

    public function lock(string $id, bool $deleted = false): array
    {
        return ($deleted ? Item::onlyTrashed() : Item::query())->whereKey($id)->lockForUpdate()->firstOrFail()->attributesToArray();
    }

    public function conversionRatios(string $id): array
    {
        return ItemUnit::where('item_id', $id)->get()->pluck('conversion_ratio', 'unit_id')->all();
    }

    public function update(string $id, CreateItemData $data): void
    {
        $item = Item::findOrFail($id);
        $item->update(['sku' => $data->sku, 'name' => $data->name, 'is_active' => $data->isActive]);
        $selected = [];
        foreach ($data->units as $unit) {
            $selected[] = $unit->unitId;
            $relation = $item->units()->withTrashed()->firstOrNew(['unit_id' => $unit->unitId]);
            $relation->fill([
                'conversion_ratio' => $unit->conversionRatio, 'barcode' => $unit->barcode,
                'is_sale_unit' => $unit->isSaleUnit, 'is_purchase_unit' => $unit->isPurchaseUnit,
            ]);
            if ($relation->exists && $relation->trashed()) {
                $relation->restore();
            } else {
                $relation->save();
            }
        }
        foreach ($item->units()->whereNotIn('unit_id', $selected)->get() as $relation) {
            $relation->delete();
        }
    }

    public function delete(string $id): void
    {
        Item::findOrFail($id)->delete();
    }

    public function restore(string $id): void
    {
        Item::onlyTrashed()->findOrFail($id)->restore();
    }

    public function isReferenced(string $unitId): bool
    {
        return Item::withTrashed()->where('base_unit_id', $unitId)->exists()
            || ItemUnit::withTrashed()->where('unit_id', $unitId)->exists();
    }

    public function paginate(int $page, array $filters = []): array
    {
        $query = ! empty($filters['trashed']) ? Item::onlyTrashed() : Item::query();
        $query->when($filters['search'] ?? '', fn ($query, $search) => $query->where(fn ($row) => $row
            ->where('sku', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')
            ->orWhereHas('units', fn ($unit) => $unit->where('barcode', 'like', '%'.$search.'%'))));
        if (in_array($filters['status'] ?? 'all', ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $columns = ['id', 'sku', 'name', 'base_unit_id', 'is_active'];
        if ($filters !== []) {
            $columns[] = 'deleted_at';
        }
        $paginator = $query->with(['media', 'units' => fn ($query) => $query
            ->select(['id', 'item_id', 'unit_id', 'conversion_ratio', 'barcode', 'is_sale_unit', 'is_purchase_unit'])->orderBy('id')])
            ->orderBy('sku')->orderBy('id')->paginate($this->pagination->perPage(), $columns, page: $page);

        return [
            'data' => $paginator->getCollection()->map(fn (Item $item) => [...$item->toArray(), 'image_url' => $item->imageUrl()])->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
            ],
        ];
    }

    public function findIdBySku(string $sku): ?string
    {
        return Item::withTrashed()->where('sku', $sku)->value('id');
    }

    public function create(CreateItemData $data): string
    {
        $item = Item::query()->create([
            'sku' => $data->sku,
            'name' => $data->name,
            'base_unit_id' => $data->baseUnitId,
            'is_active' => $data->isActive,
        ]);

        foreach ($data->units as $unit) {
            $item->units()->create([
                'unit_id' => $unit->unitId,
                'conversion_ratio' => $unit->conversionRatio,
                'barcode' => $unit->barcode,
                'is_sale_unit' => $unit->isSaleUnit,
                'is_purchase_unit' => $unit->isPurchaseUnit,
            ]);
        }

        return $item->id;
    }
}
