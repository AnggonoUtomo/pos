<?php

namespace App\Modules\Sales\Returns\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Sales\Returns\Infrastructure\Persistence\Models\SalesReturn;
use Brick\Math\BigDecimal;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

final class EloquentReturnDraftRepository implements ReturnDraftRepository
{
    private function requireTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Mutation/lock draft memerlukan transaction.');
        }
    }

    public function lock(string $id, bool $trashed = false): array
    {
        $this->requireTransaction();

        return ($trashed ? SalesReturn::onlyTrashed() : SalesReturn::query())->lockForUpdate()->findOrFail($id)->toArray();
    }

    public function save(array $header, array $lines, ?string $id = null): string
    {
        $this->requireTransaction();
        $model = $id === null ? new SalesReturn : SalesReturn::query()->lockForUpdate()->findOrFail($id);
        $sameHeader = $id !== null && collect($header)->every(fn ($value, $key) => $model->getAttribute($key) == $value);
        $model->fill($header);
        if ($sameHeader && ($this->lines([$id])[$id] ?? collect())->map(fn ($row) => Arr::except($row, ['id', 'sales_return_id']))->all() == $lines) {
            return $id;
        }
        if ($id === null) {
            $id = strtolower((string) Str::ulid());
            $model->fill(['id' => $id, 'number' => 'SR-'.strtoupper($id), 'status' => 'draft', 'version' => 1]);
            $event = 'created';
        } else {
            $model->version++;
            $event = 'updated';
        }
        if (! $model->save()) {
            throw new RuntimeException('Draft retur gagal disimpan.');
        }
        DB::table('sales_return_lines')->where('sales_return_id', $id)->delete();
        foreach ($lines as $line) {
            DB::table('sales_return_lines')->insert(array_merge($line, ['id' => strtolower((string) Str::ulid()),
                'sales_return_id' => $id, 'snapshot' => json_encode($line['snapshot'], JSON_THROW_ON_ERROR)]));
        }
        $this->audit($model, $event);

        return $id;
    }

    public function lifecycle(string $id, bool $restore): void
    {
        $this->requireTransaction();
        $model = ($restore ? SalesReturn::onlyTrashed() : SalesReturn::query())->lockForUpdate()->findOrFail($id);
        $model->version++;
        if ($restore ? ! $model->restore() : (! $model->save() || ! $model->delete())) {
            throw new RuntimeException('Status draft retur gagal disimpan.');
        }
        $this->audit($model, $restore ? 'restored' : 'deleted');
    }

    private function audit(SalesReturn $model, string $event): void
    {
        $log = activity('sales.returns')->performedOn($model)->event($event)->withProperties([
            'attributes' => Arr::only($model->toArray(), ['number', 'invoice_id', 'status', 'version']),
            'line_count' => DB::table('sales_return_lines')->where('sales_return_id', $model->id)->count(),
        ])->log('Draft retur '.$event);
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit retur gagal disimpan.');
        }
    }

    public function paginate(int $page, string $search, bool $trashed): array
    {
        $query = $trashed ? SalesReturn::onlyTrashed() : SalesReturn::query();
        if ($search !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn ($q) => $q->whereRaw("number LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("invoice_number LIKE ? ESCAPE '!'", [$like])->orWhereRaw("customer_name LIKE ? ESCAPE '!'", [$like]));
        }
        $result = $query->orderByDesc('id')->paginate(25, page: $page);
        $lines = $this->lines($result->getCollection()->pluck('id')->all());

        return ['data' => $result->getCollection()->map(fn ($model) => Arr::except($model->toArray(), ['demo_key']) + ['lines' => ($lines[$model->id] ?? collect())->all()])->all(),
            'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => 25, 'total' => $result->total()]];
    }

    private function lines(array $ids): Collection
    {
        return DB::table('sales_return_lines')->whereIn('sales_return_id', $ids)->orderBy('invoice_line_id')->get()->map(function ($row): array {
            $line = (array) $row;
            $line['snapshot'] = json_decode($line['snapshot'], true, flags: JSON_THROW_ON_ERROR);
            foreach (['quantity', 'base_quantity'] as $key) {
                $line[$key] = (string) BigDecimal::of($line[$key])->toScale(6);
            }

            return $line;
        })->groupBy('sales_return_id');
    }

    public function demoExists(string $key): bool
    {
        return SalesReturn::withTrashed()->where('demo_key', $key)->exists();
    }
}
