<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Items\Application\DTOs\SaleItemData;
use App\Modules\Inventory\Items\Application\DTOs\SaleUnitData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EloquentSaleItemCatalog implements SaleItemCatalog
{
    public function __construct(private UnitLookup $units) {}

    private function query(): Builder
    {
        return Item::where('is_active', true)->whereHas('units', fn ($q) => $q->where('is_sale_unit', true))
            ->with(['units' => fn ($q) => $q->where('is_sale_unit', true)->orderBy('id')]);
    }

    public function search(string $query, int $page): array
    {
        if ($page < 1 || $page > 100000 || mb_strlen($query) > 150) {
            throw new InvalidArgumentException('Pencarian katalog tidak valid.');
        }
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower(trim($query))).'%';
        $rows = $this->query()->where(fn ($q) => $q->whereRaw("LOWER(sku) LIKE ? ESCAPE '!'", [$pattern])
            ->orWhereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern])
            ->orWhereHas('units', fn ($u) => $u->where('is_sale_unit', true)->whereRaw("LOWER(barcode) LIKE ? ESCAPE '!'", [$pattern])))
            ->orderBy('sku')->orderBy('id')->offset(($page - 1) * 25)->limit(26)->get();

        $needle = mb_strtolower(trim($query));
        $visible = array_filter($this->map($rows->take(25)), static function (SaleItemData $item) use ($needle): bool {
            if (str_contains(mb_strtolower($item->sku), $needle) || str_contains(mb_strtolower($item->name), $needle)) {
                return true;
            }
            foreach ($item->units as $unit) {
                if ($unit->barcode !== null && str_contains(mb_strtolower($unit->barcode), $needle)) {
                    return true;
                }
            }

            return false;
        });

        return ['data' => array_values($visible), 'has_more' => $rows->count() > 25];
    }

    public function findMany(array $ids, bool $lock = false): array
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new \LogicException('Katalog lock memerlukan transaction default.');
        }
        if (count($ids) > 100) {
            throw new InvalidArgumentException('Batch barang melebihi batas.');
        }

        $discovery = $this->query()->whereIn('id', $ids)->get();
        if (! $lock) {
            return $this->map($discovery);
        }
        $unitIds = $discovery->flatMap(fn ($item) => [$item->base_unit_id, ...$item->units->pluck('unit_id')->all()])->unique()->sort()->values()->all();
        $units = [];
        foreach ($unitIds as $id) {
            $units[$id] = $this->units->findActiveById($id, lock: true);
        }
        $rows = Item::whereIn('id', $ids)->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
        foreach ($rows as $item) {
            $item->setRelation('units', $item->units()->where('is_sale_unit', true)->orderBy('id')->lockForUpdate()->get());
            foreach ([$item->base_unit_id, ...$item->units->pluck('unit_id')->all()] as $id) {
                if (! array_key_exists($id, $units)) {
                    throw new InvalidArgumentException('Konfigurasi satuan berubah. Periksa ulang keranjang.');
                }
            }
        }

        return $this->map($rows, $units);
    }

    private function map(Collection $rows, ?array $lockedUnits = null): array
    {
        $ids = $rows->flatMap(fn ($item) => [$item->base_unit_id, ...$item->units->pluck('unit_id')->all()])->unique()->values()->all();
        $units = $lockedUnits ?? $this->units->findManyActive($ids);
        $result = [];
        foreach ($rows as $item) {
            $base = $units[$item->base_unit_id] ?? null;
            if ($base === null) {
                continue;
            }
            $sale = [];
            foreach ($item->units as $row) {
                $unit = $units[$row->unit_id] ?? null;
                if ($unit !== null) {
                    $sale[] = new SaleUnitData($unit->id, $unit->code, $unit->name, $row->conversion_ratio, $row->barcode);
                }
            }
            if ($sale !== []) {
                $result[$item->id] = new SaleItemData($item->id, $item->sku, $item->name, $base->id, $base->name, $sale);
            }
        }

        return $result;
    }
}
