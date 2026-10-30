<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\CashbookReader;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use Brick\Math\BigDecimal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DatabaseCashbookReader implements CashbookReader
{
    public function __construct(private TablePagination $pagination) {}

    public function accounts(): array
    {
        return DB::table('payment_accounts as a')->leftJoin('cashbook_openings as o', 'o.account_id', '=', 'a.id')
            ->whereNull('a.deleted_at')->orderBy('a.name')->get(['a.id', 'a.code', 'a.name', 'a.type', 'a.is_active', 'o.starts_on'])
            ->map(fn ($row) => (array) $row)->all();
    }

    private function legacy(string $account, string $header, string $lines, string $foreign, string $time, string $amount, string $kind, string $direction): Builder
    {
        return DB::table($header.' as h')->join($lines.' as l', 'l.'.$foreign, '=', 'h.id')->where('l.account_id', $account)
            ->select(['l.id as row_id', 'h.source_number as number', 'l.method_name as description', 'h.'.$time.' as occurred_at'])
            ->selectRaw('CAST(l.'.$amount.' AS CHAR) as amount')->selectRaw('? as kind, ? as direction, NULL as reversible_id', [$kind, $direction]);
    }

    private function manual(string $account, bool $counter): Builder
    {
        return DB::table('cashbook_entries as e')->where($counter ? 'e.counter_account_id' : 'e.account_id', $account)
            ->select(['e.id as row_id', 'e.id as number', 'e.description', 'e.occurred_at'])
            ->selectRaw('CAST(e.amount AS CHAR) as amount, e.kind')
            ->selectRaw($counter ? "CASE WHEN e.direction = 'incoming' THEN 'outgoing' ELSE 'incoming' END as direction" : 'e.direction')
            ->selectRaw("CASE WHEN e.kind = 'reversal' OR EXISTS (SELECT 1 FROM cashbook_entries r WHERE r.reversed_entry_id = e.id) THEN NULL ELSE e.id END as reversible_id");
    }

    private function movements(string $account): Builder
    {
        $union = $this->legacy($account, 'payment_receipts', 'payment_records', 'receipt_id', 'posted_at', 'applied_amount', 'sale', 'incoming')
            ->unionAll($this->legacy($account, 'payment_disbursements', 'payment_disbursement_lines', 'disbursement_id', 'paid_at', 'amount', 'purchase', 'outgoing'))
            ->unionAll($this->legacy($account, 'payment_refunds', 'payment_refund_lines', 'refund_id', 'paid_at', 'amount', 'sale_return', 'outgoing'))
            ->unionAll($this->legacy($account, 'purchase_refund_receipts', 'purchase_refund_receipt_lines', 'receipt_id', 'received_at', 'amount', 'purchase_return', 'incoming'))
            ->unionAll($this->manual($account, false))->unionAll($this->manual($account, true));

        return DB::query()->fromSub($union, 'movements');
    }

    private function midnight(string $date): Carbon
    {
        return Carbon::parse($date, 'Asia/Jakarta')->startOfDay()->setTimezone(config('app.timezone'));
    }

    public function statement(?string $accountId, ?string $from, ?string $to, int $page): array
    {
        $perPage = $this->pagination->perPage();

        // One read snapshot, decimal strings and a cursor avoid float sums and loading all history into memory.
        return DB::transaction(function () use ($accountId, $from, $to, $page, $perPage): array {
            $account = $accountId ? DB::table('payment_accounts')->where('id', $accountId)->whereNull('deleted_at')->first(['id', 'code', 'name', 'type', 'is_active']) : null;
            abort_if($accountId !== null && $account === null, 404);
            $opening = $account ? DB::table('cashbook_openings')->where('account_id', $accountId)->first(['starts_on', 'amount']) : null;
            $result = ['account' => $account ? (array) $account : null, 'opening' => $opening ? ['starts_on' => $opening->starts_on, 'amount' => (string) BigDecimal::of($opening->amount)->toScale(6)] : null,
                'summary' => null, 'rows' => [], 'meta' => ['current_page' => $page, 'last_page' => 1, 'per_page' => $perPage, 'total' => 0]];
            if ($opening === null) {
                return $result;
            }
            if ($to !== null && $to < $opening->starts_on) {
                throw ValidationException::withMessages(['date_to' => 'Periode berakhir sebelum tanggal mulai Buku Kas akun ini.']);
            }
            $start = max($from ?? $opening->starts_on, $opening->starts_on);
            $until = $this->midnight($to ?? now('Asia/Jakarta')->toDateString())->addDay();
            $query = $this->movements($accountId)->where('occurred_at', '>=', $this->midnight($opening->starts_on))->where('occurred_at', '<', $until)
                ->orderBy('occurred_at')->orderBy('kind')->orderBy('row_id');
            $balance = BigDecimal::of($opening->amount)->toScale(6);
            $periodOpening = $balance;
            $incoming = $outgoing = BigDecimal::zero()->toScale(6);
            $offset = ($page - 1) * $perPage;
            $count = 0;
            foreach ($query->cursor() as $row) {
                $amount = BigDecimal::of((string) $row->amount)->toScale(6);
                $balance = $row->direction === 'incoming' ? $balance->plus($amount) : $balance->minus($amount);
                if ($row->occurred_at < $this->midnight($start)->format('Y-m-d H:i:s')) {
                    $periodOpening = $balance;

                    continue;
                }
                if ($row->direction === 'incoming') {
                    $incoming = $incoming->plus($amount);
                } else {
                    $outgoing = $outgoing->plus($amount);
                }
                if ($count >= $offset && $count < $offset + $perPage) {
                    $record = (array) $row;
                    $record['amount'] = (string) $amount;
                    $record['balance'] = (string) $balance;
                    $record['occurred_at'] = Carbon::parse($row->occurred_at, config('app.timezone'))->utc()->toISOString();
                    if (in_array($row->kind, ['incoming', 'outgoing', 'transfer', 'reversal'], true)) {
                        $record['number'] = 'BK-'.$row->number;
                    }
                    $result['rows'][] = $record;
                }
                $count++;
            }
            $result['summary'] = ['opening' => (string) $periodOpening, 'incoming' => (string) $incoming, 'outgoing' => (string) $outgoing, 'closing' => (string) $balance, 'negative' => $balance->isNegative()];
            $result['meta']['total'] = $count;
            $result['meta']['last_page'] = max(1, (int) ceil($count / $perPage));

            return $result;
        });
    }
}
