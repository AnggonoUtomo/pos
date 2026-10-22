<?php

namespace App\Modules\Purchasing\Returns\Infrastructure\Persistence\Repositories;

use App\Modules\Purchasing\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Purchasing\Returns\Infrastructure\Persistence\Models\PurchaseReturn;
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
            throw new LogicException('Mutation/lock draft retur memerlukan transaction.');
        }
    }

    public function lock(string $id, bool $trashed = false): array
    {
        $this->requireTransaction();
        $query = $trashed ? PurchaseReturn::onlyTrashed() : PurchaseReturn::query();
        $model = $query->lockForUpdate()->findOrFail($id);

        return $model->toArray() + ['lines' => ($this->lines([$id], lock: true)[$id] ?? collect())->all()];
    }

    public function save(array $header, array $lines, ?string $id = null, ?string $demoKey = null): string
    {
        $this->requireTransaction();
        $model = $id === null ? new PurchaseReturn : PurchaseReturn::query()->lockForUpdate()->findOrFail($id);
        $sameHeader = $id !== null && collect($header)->every(fn ($value, $key) => $key === 'snapshot'
            ? $this->sameSnapshot($model->getAttribute($key), $value)
            : $model->getAttribute($key) === $value);
        if ($sameHeader && $this->sameLines(($this->lines([$id])[$id] ?? collect())->all(), $lines)) {
            return $id;
        }

        $model->fill($header);
        if ($id === null) {
            $id = strtolower((string) Str::ulid());
            $model->fill(['id' => $id, 'number' => 'PR-'.strtoupper($id), 'status' => 'draft', 'version' => 1, 'demo_key' => $demoKey]);
            $event = 'created';
        } else {
            $model->version++;
            $event = 'updated';
        }
        if (! $model->save()) {
            throw new RuntimeException('Draft retur pembelian gagal disimpan.');
        }

        DB::table('purchase_return_lines')->where('purchase_return_id', $id)->delete();
        foreach ($lines as $line) {
            DB::table('purchase_return_lines')->insert(array_merge(Arr::except($line, ['snapshot']), [
                'id' => strtolower((string) Str::ulid()), 'purchase_return_id' => $id,
                'snapshot' => json_encode($line['snapshot'], JSON_THROW_ON_ERROR),
            ]));
        }
        $this->audit($model, $event);

        return $id;
    }

    public function delete(string $id): void
    {
        $this->requireTransaction();
        $model = PurchaseReturn::query()->lockForUpdate()->findOrFail($id);
        $model->version++;
        if (! $model->save() || ! $model->delete()) {
            throw new RuntimeException('Draft retur gagal dihapus.');
        }
        $this->audit($model, 'deleted');
    }

    public function restore(string $id): void
    {
        $this->requireTransaction();
        $model = PurchaseReturn::onlyTrashed()->lockForUpdate()->findOrFail($id);
        $model->version++;
        if (! $model->restore()) {
            throw new RuntimeException('Draft retur gagal dipulihkan.');
        }
        $this->audit($model, 'restored');
    }

    public function post(string $id, string $issueId, string $postedAt, int $version, ?int $actorId): void
    {
        $this->requireTransaction();
        $model = PurchaseReturn::query()->lockForUpdate()->findOrFail($id);
        $model->fill(['status' => 'posted', 'version' => $version + 1, 'posted_from_version' => $version,
            'stock_issue_id' => $issueId, 'posted_at' => $postedAt, 'posted_by' => $actorId]);
        if (! $model->save()) {
            throw new RuntimeException('Status posting retur pembelian gagal disimpan.');
        }
        $this->audit($model, 'posted', $actorId);
    }

    public function demoExists(string $key): bool
    {
        return PurchaseReturn::withTrashed()->where('demo_key', $key)->exists();
    }

    private function audit(PurchaseReturn $model, string $event, ?int $actorId = null): void
    {
        $logger = activity('purchasing.returns')->performedOn($model)->event($event)->withProperties([
            'attributes' => Arr::only($model->toArray(), ['number', 'purchase_order_id', 'status', 'version', 'deleted_at', 'stock_issue_id', 'posted_at']),
            'line_count' => DB::table('purchase_return_lines')->where('purchase_return_id', $model->id)->count(),
        ]);
        if ($event === 'posted') {
            $actorId === null ? $logger->causedByAnonymous() : $logger->causedBy($actorId);
        }
        $log = $logger->log('Retur pembelian '.$event);
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit draft retur pembelian gagal disimpan.');
        }
    }

    public function paginate(int $page, array $filters): array
    {
        $query = ! empty($filters['trashed']) ? PurchaseReturn::onlyTrashed() : PurchaseReturn::query();
        if (in_array($filters['status'] ?? 'all', ['draft', 'posted'], true)) {
            $query->where('status', $filters['status']);
        }
        if (($search = $filters['search'] ?? '') !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->whereRaw("(number LIKE ? ESCAPE '!' OR purchase_order_number LIKE ? ESCAPE '!' OR supplier_name LIKE ? ESCAPE '!')", [$like, $like, $like]);
        }
        $result = $query->orderByDesc('id')->paginate(25, page: $page);
        $lines = $this->lines($result->getCollection()->pluck('id')->all());

        return ['data' => $result->getCollection()->map(fn ($model) => Arr::except($model->toArray(), ['demo_key', 'posted_by', 'posted_from_version'])
            + ['lines' => ($lines[$model->id] ?? collect())->all()])->all(),
            'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => 25, 'total' => $result->total()]];
    }

    private function lines(array $ids, bool $lock = false): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return DB::table('purchase_return_lines')->whereIn('purchase_return_id', $ids)->orderBy('purchase_return_id')->orderBy('purchase_order_line_id')
            ->when($lock, fn ($query) => $query->lockForUpdate())->get()->map(function ($row): array {
                $line = (array) $row;
                $line['snapshot'] = json_decode($line['snapshot'], true, flags: JSON_THROW_ON_ERROR);
                foreach (['quantity', 'base_quantity'] as $field) {
                    $line[$field] = (string) BigDecimal::of((string) $line[$field])->toScale(6);
                }

                return $line;
            })->groupBy('purchase_return_id');
    }

    private function sameSnapshot(array $left, array $right): bool
    {
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
            foreach (['purchase_order_line_id', 'quantity', 'base_quantity'] as $key) {
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
