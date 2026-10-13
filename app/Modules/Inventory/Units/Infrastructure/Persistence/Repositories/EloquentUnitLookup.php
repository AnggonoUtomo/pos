<?php

namespace App\Modules\Inventory\Units\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Application\DTOs\UnitData;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Support\Facades\DB;
use LogicException;

class EloquentUnitLookup implements UnitLookup
{
    public function findManyActive(array $ids): array
    {
        if (count($ids) > 11000) {
            throw new \InvalidArgumentException('Batch satuan melebihi batas.');
        }

        return Unit::whereIn('id', $ids)->where('is_active', true)->get()
            ->mapWithKeys(fn (Unit $unit) => [$unit->id => $this->toData($unit)])->all();
    }

    public function listActive(): array
    {
        return Unit::query()->where('is_active', true)->orderBy('code')->orderBy('id')
            ->limit(1000)->get(['id', 'code', 'name', 'symbol'])
            ->map(fn (Unit $unit): UnitData => $this->toData($unit))->all();
    }

    public function findActiveById(string $id, bool $lock = false): ?UnitData
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new LogicException('Lookup dengan lock memerlukan transaction.');
        }

        $unit = Unit::query()->whereKey($id)->where('is_active', true)
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();

        return $unit ? $this->toData($unit) : null;
    }

    public function findActiveByCode(string $code): ?UnitData
    {
        $unit = Unit::query()->where('code', $code)->where('is_active', true)->first();

        return $unit ? $this->toData($unit) : null;
    }

    private function toData(Unit $unit): UnitData
    {
        return new UnitData($unit->id, $unit->code, $unit->name, $unit->symbol);
    }
}
