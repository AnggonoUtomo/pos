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

        return ($trashed ? SalesReturn::onlyTrashed() : SalesReturn::query())->lockForUpdate()->findOrFail($id)->toArray()
            + ['lines' => ($this->lines([$id], lock: true)[$id] ?? collect())->all()];
    }

    public function save(array $header, array $lines, ?string $id = null): string
    {
        $this->requireTransaction();
        $model = $id === null ? new SalesReturn : SalesReturn::query()->lockForUpdate()->findOrFail($id);
        $sameHeader = $id !== null && collect($header)->every(fn ($value, $key) => $key === 'snapshot'
            ? $this->sameSnapshot($model->getAttribute($key), $value)
            : $model->getAttribute($key) === $value);
        $model->fill($header);
        if ($sameHeader && $this->sameLines(($this->lines([$id])[$id] ?? collect())->all(), $lines)) {
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

    public function post(string $id, string $receiptId, string $postedAt, int $version, ?int $actorId): void
    {
        $this->requireTransaction();
        $model = SalesReturn::query()->lockForUpdate()->findOrFail($id);
        $model->fill(['status' => 'posted', 'version' => $version + 1, 'posted_from_version' => $version,
            'stock_receipt_id' => $receiptId, 'posted_at' => $postedAt, 'posted_by' => $actorId]);
        if (! $model->save()) {
            throw new RuntimeException('Status penerimaan retur gagal disimpan.');
        }
        $this->audit($model, 'posted', $actorId);
    }

    private function audit(SalesReturn $model, string $event, ?int $actorId = null): void
    {
        $logger = activity('sales.returns')->performedOn($model)->event($event)->withProperties([
            'attributes' => Arr::only($model->toArray(), ['number', 'invoice_id', 'status', 'version', 'stock_receipt_id', 'posted_at']),
            'line_count' => DB::table('sales_return_lines')->where('sales_return_id', $model->id)->count(),
        ]);
        if ($event === 'posted') {
            $actorId === null ? $logger->causedByAnonymous() : $logger->causedBy($actorId);
        }
        $log = $logger->log('Retur '.$event);
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

        return ['data' => $result->getCollection()->map(fn ($model) => Arr::except($model->toArray(), ['demo_key', 'posted_by', 'posted_from_version']) + ['lines' => ($lines[$model->id] ?? collect())->all()])->all(),
            'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => 25, 'total' => $result->total()]];
    }

    private function lines(array $ids, bool $lock = false): Collection
    {
        return DB::table('sales_return_lines')->whereIn('sales_return_id', $ids)->orderBy('invoice_line_id')->when($lock, fn ($q) => $q->lockForUpdate())->get()->map(function ($row): array {
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

    private function sameSnapshot(array $left, array $right): bool
    {
        // MySQL JSON dapat mengurutkan key; nilai decimal tetap dibandingkan sebagai string.
        ksort($left);
        ksort($right);

        return $left === $right;
    }

    private function sameLines(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $index => $line) {
            foreach (['invoice_line_id', 'quantity', 'base_quantity'] as $key) {
                if ($line[$key] !== $right[$index][$key]) {
                    return false;
                }
            }
            if (! $this->sameSnapshot($line['snapshot'], $right[$index]['snapshot'])) {
                return false;
            }
        }

        return true;
    }
}
