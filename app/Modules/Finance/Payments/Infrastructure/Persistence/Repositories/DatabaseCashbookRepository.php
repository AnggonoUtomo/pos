<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\CashbookRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\CashbookEntry;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\CashbookOpening;
use Illuminate\Support\Facades\DB;

class DatabaseCashbookRepository implements CashbookRepository
{
    public function opening(string $accountId, bool $lock = false): ?array
    {
        $query = CashbookOpening::where('account_id', $accountId);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first()?->attributesToArray();
    }

    public function entry(string $id, bool $lock = false): ?array
    {
        $query = CashbookEntry::whereKey($id);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first()?->attributesToArray();
    }

    public function entryByKey(string $key): ?array
    {
        return CashbookEntry::where('idempotency_key', $key)->lockForUpdate()->first()?->attributesToArray();
    }

    public function createOpening(array $data): string
    {
        return CashbookOpening::create($data)->id;
    }

    public function createEntry(array $data): string
    {
        return CashbookEntry::create($data)->id;
    }

    public function audit(string $type, string $id, int $actor): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Audit Buku Kas memerlukan transaksi.');
        }
        $subject = $type === 'opening' ? CashbookOpening::findOrFail($id) : CashbookEntry::findOrFail($id);
        $audit = activity('finance.cashbook')->performedOn($subject)->causedBy($actor)->event($type)
            ->withProperties(['account_id' => $subject->account_id, 'kind' => $subject->kind ?? 'opening', 'reversed_entry_id' => $subject->reversed_entry_id])
            ->log('Pencatatan Buku Kas');
        if ($audit === null || ! $audit->exists) {
            throw new \RuntimeException('Audit Buku Kas tidak tersimpan.');
        }
    }
}
