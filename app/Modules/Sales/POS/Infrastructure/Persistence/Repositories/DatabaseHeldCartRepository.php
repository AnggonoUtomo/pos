<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\POS\Application\Contracts\HeldCartRepository;
use App\Modules\Sales\POS\Infrastructure\Persistence\Models\HeldCart;
use Illuminate\Support\Facades\DB;

class DatabaseHeldCartRepository implements HeldCartRepository
{
    private function transaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Mutation/lock held cart memerlukan transaction default.');
        }
    }

    public function findOwned(string $id, int $actorId, bool $lock = false): ?array
    {
        if ($lock) {
            $this->transaction();
        }

        return HeldCart::withTrashed()->whereKey($id)->where('actor_id', $actorId)
            ->when($lock, fn ($q) => $q->lockForUpdate())->first()?->toArray();
    }

    public function listOwned(int $actorId, int $page): array
    {
        $paginator = HeldCart::where('actor_id', $actorId)->whereNull('invoice_id')
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(25, page: $page);

        return ['data' => $paginator->map(fn ($row) => ['id' => $row->id, 'version' => $row->version, 'label' => $row->label,
            'warehouse_name' => $row->warehouse_name, 'customer_name' => $row->customer_name,
            'line_count' => count($row->payload['lines']), 'updated_at' => $row->updated_at->utc()->toISOString()])->all(),
            'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'total' => $paginator->total()]];
    }

    public function write(string $id, array $attributes, bool $create = false): array
    {
        $this->transaction();
        $row = $create ? HeldCart::create(['id' => $id, ...$attributes]) : HeldCart::findOrFail($id);
        if (! $create) {
            $row->update($attributes);
        }

        return $row->fresh()->toArray();
    }

    public function cancel(string $id): void
    {
        $this->transaction();
        $row = HeldCart::findOrFail($id);
        $row->increment('version');
        $row->delete();
    }

    public function audit(string $id, int $actorId, string $event): void
    {
        $this->transaction();
        $row = HeldCart::withTrashed()->findOrFail($id);
        $audit = activity('sales.pos.held_carts')->performedOn($row)->causedBy($actorId)->event($event)
            ->withProperties(['version' => $row->version, 'invoice_id' => $row->invoice_id])->log('Keranjang POS '.$event);
        if ($audit === null || ! $audit->exists) {
            throw new \RuntimeException('Audit keranjang tertahan tidak tersimpan.');
        }
    }
}
