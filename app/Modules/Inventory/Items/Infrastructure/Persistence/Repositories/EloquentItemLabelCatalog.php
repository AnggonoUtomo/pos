<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Items\Application\Contracts\ItemLabelCatalog;
use App\Modules\Inventory\Items\Application\DTOs\ItemLabelData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemUnit;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EloquentItemLabelCatalog implements ItemLabelCatalog
{
    public function __construct(private UnitLookup $units) {}

    private function query(): Builder
    {
        return ItemUnit::query()->join('items', 'items.id', '=', 'item_units.item_id')
            ->whereNull('items.deleted_at')->where('items.is_active', true)
            ->whereNotNull('item_units.barcode')->where('item_units.barcode', '!=', '')
            ->select(['item_units.item_id', 'item_units.unit_id', 'item_units.barcode', 'items.sku', 'items.name', 'items.base_unit_id']);
    }

    private function map(iterable $rows): array
    {
        $rows = collect($rows);
        $units = $this->units->findManyActive($rows->flatMap(fn ($r) => [$r->unit_id, $r->base_unit_id])->unique()->values()->all());

        return $rows->filter(fn ($r) => isset($units[$r->unit_id], $units[$r->base_unit_id]))
            ->map(fn ($r) => new ItemLabelData($r->item_id, $r->unit_id, $r->sku, $r->name, $units[$r->unit_id]->name, $r->barcode))->values()->all();
    }

    public function search(string $query, int $page): array
    {
        if ($page < 1 || $page > 100000 || mb_strlen($query) > 150) {
            throw new InvalidArgumentException('Pencarian label tidak valid.');
        }
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower(trim($query))).'%';
        $rows = $this->query()->where(fn ($q) => $q->whereRaw("LOWER(items.name) LIKE ? ESCAPE '!'", [$pattern])
            ->orWhereRaw("LOWER(items.sku) LIKE ? ESCAPE '!'", [$pattern])
            ->orWhereRaw("LOWER(item_units.barcode) LIKE ? ESCAPE '!'", [$pattern]))
            ->orderBy('items.sku')->orderBy('item_units.id')->offset(($page - 1) * 25)->limit(26)->get();

        return ['data' => $this->map($rows->take(25)), 'has_more' => $rows->count() > 25];
    }

    public function find(string $itemId, string $unitId): ?ItemLabelData
    {
        if (! Str::isUlid($itemId) || ! Str::isUlid($unitId)) {
            return null;
        }
        $rows = $this->query()->where('item_units.item_id', strtolower($itemId))->where('item_units.unit_id', strtolower($unitId))->limit(1)->get();

        return $this->map($rows)[0] ?? null;
    }
}
