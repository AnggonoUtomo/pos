<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\CashbookReader;
use App\Modules\Finance\Payments\Application\Contracts\CashDrawerPosition;
use App\Modules\Finance\Payments\Application\DTOs\CashDrawerData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DatabaseCashDrawerPosition implements CashDrawerPosition
{
    public function __construct(private CashbookReader $book) {}

    public function choices(): array
    {
        return array_values(array_map(fn ($account) => array_intersect_key($account, array_flip(['id', 'code', 'name'])), array_filter($this->book->accounts(), fn ($account) => $account['type'] === 'cash' && $account['is_active'] && $account['starts_on'] !== null)));
    }

    public function read(string $accountId, bool $lock = false): CashDrawerData
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new \LogicException('Posisi kas terkunci memerlukan transaction caller.');
        }
        $account = DB::table('payment_accounts')->where('id', $accountId)->whereNull('deleted_at')->when($lock, fn ($q) => $q->lockForUpdate())->first(['id', 'code', 'name', 'type', 'is_active']);
        $opening = DB::table('cashbook_openings')->where('account_id', $accountId)->when($lock, fn ($q) => $q->lockForUpdate())->first(['starts_on']);
        if ($account === null || ! $account->is_active || $account->type !== 'cash' || $opening === null) {
            throw ValidationException::withMessages(['account_id' => 'Pilih akun kas aktif dengan saldo awal Buku Kas yang sudah ditetapkan.']);
        }
        $balance = $this->book->statement($accountId, null, null, 1)['summary']['closing'];

        return new CashDrawerData($accountId, $account->code, $account->name, $balance, $opening->starts_on, now()->utc()->toISOString());
    }
}
