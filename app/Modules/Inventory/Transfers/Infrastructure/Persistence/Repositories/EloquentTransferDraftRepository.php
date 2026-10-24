<?php

namespace App\Modules\Inventory\Transfers\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Transfers\Application\Contracts\TransferDraftRepository;
use App\Modules\Inventory\Transfers\Infrastructure\Persistence\Models\StockTransfer;
use Brick\Math\BigDecimal;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class EloquentTransferDraftRepository implements TransferDraftRepository
{
    private function requireTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Mutation/lock memerlukan transaction.');
        }
    }

    public function lock(string $id, bool $deleted = false): array
    {
        $this->requireTransaction();

        $model = ($deleted ? StockTransfer::onlyTrashed() : StockTransfer::query())->lockForUpdate()->findOrFail($id);
        $lines = DB::table('stock_transfer_lines')->where('transfer_id', $id)->orderBy('item_id')->lockForUpdate()->get()
            ->map(fn ($row) => $this->normalizeLine((array) $row))->all();

        return Arr::except($model->toArray(), ['demo_key']) + ['lines' => $lines];
    }

    public function save(array $header, array $lines, ?string $id = null): string
    {
        $this->requireTransaction();
        $model = $id === null ? new StockTransfer : StockTransfer::query()->lockForUpdate()->findOrFail($id);
        $model->fill($header);
        if ($id !== null && ! $model->isDirty() && $this->existingLines($id) === $lines) {
            return $id;
        }
        if ($id === null) {
            $id = strtolower((string) Str::ulid());
            $model->fill(['id' => $id, 'number' => 'TR-'.strtoupper($id), 'status' => 'draft', 'version' => 1]);
            $event = 'created';
        } else {
            $model->version++;
            $event = 'updated';
        }
        if (! $model->save()) {
            throw new RuntimeException('Draft transfer gagal disimpan.');
        }
        DB::table('stock_transfer_lines')->where('transfer_id', $id)->delete();
        foreach ($lines as $line) {
            DB::table('stock_transfer_lines')->insert($line + ['id' => strtolower((string) Str::ulid()), 'transfer_id' => $id]);
        }
        $this->audit($model, $event);

        return $id;
    }

    private function existingLines(string $id): array
    {
        return DB::table('stock_transfer_lines')->where('transfer_id', $id)->orderBy('item_id')->lockForUpdate()->get()->map(function ($row): array {
            return Arr::except($this->normalizeLine((array) $row), ['id', 'transfer_id']);
        })->all();
    }

    public function delete(string $id): void
    {
        $this->requireTransaction();
        $model = StockTransfer::query()->lockForUpdate()->findOrFail($id);
        $model->version++;
        if (! $model->save() || ! $model->delete()) {
            throw new RuntimeException('Draft gagal dihapus.');
        }
        $this->audit($model, 'deleted');
    }

    public function restore(string $id): void
    {
        $this->requireTransaction();
        $model = StockTransfer::onlyTrashed()->lockForUpdate()->findOrFail($id);
        $model->version++;
        if (! $model->restore()) {
            throw new RuntimeException('Draft gagal dipulihkan.');
        }
        $this->audit($model, 'restored');
    }

    private function audit(StockTransfer $model, string $event): void
    {
        $logger = activity('inventory.transfers')->performedOn($model)->event($event)
            ->withProperties(['attributes' => Arr::only($model->toArray(), ['number', 'status', 'version', 'source_warehouse_id', 'destination_warehouse_id']),
                'line_count' => DB::table('stock_transfer_lines')->where('transfer_id', $model->id)->count()]);
        $log = $logger->log('Transfer '.$event);
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit draft transfer gagal disimpan.');
        }
    }

    public function markPosted(string $id, int $actorId): void
    {
        $this->requireTransaction();
        $model = StockTransfer::query()->lockForUpdate()->findOrFail($id);
        $model->fill(['status' => 'posted', 'version' => $model->version + 1, 'posted_at' => now(), 'posted_by' => $actorId]);
        if (! $model->save()) {
            throw new RuntimeException('Posting transfer gagal disimpan.');
        }
        $this->audit($model, 'posted');
    }

    public function paginate(int $page, array $filters): array
    {
        $query = ! empty($filters['trashed']) ? StockTransfer::onlyTrashed() : StockTransfer::query();
        foreach (['source_warehouse_id', 'destination_warehouse_id'] as $key) {
            $query->when($filters[$key] ?? '', fn ($q, $id) => $q->where($key, $id));
        }
        if (($search = $filters['search'] ?? '') !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn ($q) => $q->whereRaw("number LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("source_warehouse_name LIKE ? ESCAPE '!'", [$like])->orWhereRaw("destination_warehouse_name LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("source_warehouse_code LIKE ? ESCAPE '!'", [$like])->orWhereRaw("destination_warehouse_code LIKE ? ESCAPE '!'", [$like]));
        }
        $result = $query->orderByDesc('id')->paginate(25, page: $page);
        $lines = DB::table('stock_transfer_lines')->whereIn('transfer_id', $result->getCollection()->pluck('id'))->orderBy('item_id')->get()->groupBy('transfer_id');
        $data = $result->getCollection()->map(fn ($model) => Arr::except($model->toArray(), ['demo_key']) + ['lines' => ($lines[$model->id] ?? collect())->map(fn ($row) => $this->normalizeLine((array) $row))->all()])->all();

        return ['data' => $data, 'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => 25, 'total' => $result->total()]];
    }

    private function normalizeLine(array $row): array
    {
        foreach (['quantity', 'conversion_ratio', 'base_quantity'] as $field) {
            $row[$field] = (string) BigDecimal::of($row[$field])->toScale(6);
        }

        return $row;
    }

    public function demoExists(string $marker): bool
    {
        return StockTransfer::withTrashed()->where('demo_key', $marker)->exists();
    }
}
