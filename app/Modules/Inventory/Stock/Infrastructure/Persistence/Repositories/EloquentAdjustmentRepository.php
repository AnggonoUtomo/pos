<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\AdjustmentRepository;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EloquentAdjustmentRepository implements AdjustmentRepository
{
    public function find(string $id): ?array
    {
        return StockAdjustment::query()->lockForUpdate()->find($id)?->toArray();
    }

    public function create(array $record): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Adjustment memerlukan transaction caller.');
        }
        $model = StockAdjustment::create($record);
        if (! $model->exists) {
            throw new RuntimeException('Koreksi tidak tersimpan.');
        }
        $log = activity('inventory.stock')->performedOn($model)->event('adjusted')->causedBy($record['posted_by'])
            ->withProperties(['number' => $model->number, 'direction' => $model->direction, 'quantity' => $model->quantity,
                'warehouse_id' => $model->warehouse_id, 'item_id' => $model->item_id, 'reason' => $model->reason])->log('Koreksi stok');
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit koreksi tidak tersimpan.');
        }
    }
}
