<?php

namespace App\Modules\Inventory\Units\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Application\DTOs\UnitData;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Support\Facades\DB;
use LogicException;

class EloquentUnitLookup implements UnitLookup
{
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
