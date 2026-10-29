<?php

namespace Tests\Fixtures;

use App\Modules\Finance\Payments\Application\Actions\ManageCashbook;
use App\Modules\Finance\Payments\Application\Contracts\CashDrawerPosition;
use App\Modules\Sales\POS\Application\Actions\ManageCashierShift;
use App\Modules\Sales\POS\Application\Contracts\CashierShiftRepository;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CashierShiftFixture
{
    public static function open(int $actor): string
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('Fixture hanya untuk testing.');
        }
        $shifts = app(CashierShiftRepository::class);
        if ($active = $shifts->active($actor)) {
            return $active['id'];
        }
        $account = DB::table('payment_methods')->where('code', 'TUNAI')->value('payment_account_id');
        if (! DB::table('cashbook_openings')->where('account_id', $account)->exists()) {
            app(ManageCashbook::class)->open(['account_id' => $account, 'amount' => '0', 'starts_on' => '2000-01-01'], $actor);
        }
        $occupied = DB::table('cashier_shifts')->where('active_account_id', $account)->first();
        if ($occupied) {
            $balance = app(CashDrawerPosition::class)->read($account)->balance;
            app(ManageCashierShift::class)->close($occupied->id, BigDecimal::of($balance)->isNegative() ? '0' : $balance, 'Pergantian kasir fixture', (int) $occupied->actor_id);
        }
        $id = strtolower((string) Str::ulid());
        app(ManageCashierShift::class)->open($id, $account, $actor);

        return $id;
    }
}
