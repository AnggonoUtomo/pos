<?php

namespace App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Repositories;

use App\Modules\Purchasing\Suppliers\Application\Contracts\SupplierLookup;
use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierLookupData;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use LogicException;

class EloquentSupplierLookup implements SupplierLookup
{
    public function findActive(string $id, bool $lock = false): ?SupplierLookupData
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new LogicException('Lookup lock memerlukan transaction.');
        }
        $row = Supplier::where('is_active', true)->whereKey($id)->when($lock, fn ($q) => $q->lockForUpdate())->first(['id', 'code', 'name']);

        return $row ? new SupplierLookupData($row->id, $row->code, $row->name) : null;
    }

    public function choices(): array
    {
        return Supplier::where('is_active', true)->orderBy('code')->orderBy('id')->limit(1000)->get(['id', 'code', 'name'])
            ->map(fn ($row) => new SupplierLookupData($row->id, $row->code, $row->name))->all();
    }
}
