<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\PaymentHistoryReader;
use Brick\Math\BigDecimal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabasePaymentHistoryReader implements PaymentHistoryReader
{
    private const LEDGERS = [
        'incoming' => ['header' => 'payment_receipts', 'lines' => 'payment_records', 'foreign' => 'receipt_id', 'time' => 'posted_at', 'scale' => 2, 'direction' => 'incoming', 'source' => 'sale'],
        'outgoing' => ['header' => 'payment_disbursements', 'lines' => 'payment_disbursement_lines', 'foreign' => 'disbursement_id', 'time' => 'paid_at', 'scale' => 6, 'direction' => 'outgoing', 'source' => 'purchase'],
        'refund' => ['header' => 'payment_refunds', 'lines' => 'payment_refund_lines', 'foreign' => 'refund_id', 'time' => 'paid_at', 'scale' => 2, 'direction' => 'outgoing', 'source' => 'sale_return'],
        'supplier-refund' => ['header' => 'purchase_refund_receipts', 'lines' => 'purchase_refund_receipt_lines', 'foreign' => 'receipt_id', 'time' => 'received_at', 'scale' => 6, 'direction' => 'incoming', 'source' => 'purchase_return'],
    ];

    public function summary(string $from, string $until): array
    {
        $incoming = $outgoing = BigDecimal::zero()->toScale(6);
        foreach (self::LEDGERS as $key => $ledger) {
            foreach ($this->query($key, ['from' => $from, 'until' => $until])->orderBy('h.id')->cursor() as $row) {
                if ($ledger['direction'] === 'incoming') {
                    $incoming = $incoming->plus((string) $row->total);
                } else {
                    $outgoing = $outgoing->plus((string) $row->total);
                }
            }
        }

        return ['incoming' => (string) $incoming, 'outgoing' => (string) $outgoing,
            'net_movement' => (string) $incoming->minus($outgoing), 'scope' => 'all_warehouses'];
    }

    private function query(string $direction, array $filters): Builder
    {
        $ledger = self::LEDGERS[$direction];
        $query = DB::table($ledger['header'].' as h')->select('h.id', 'h.source_number')
            ->selectRaw("'{$ledger['direction']}' as direction, '{$ledger['source']}' as source_type, h.{$ledger['time']} as occurred_at, CAST(h.total AS CHAR) as total");
        if (($filters['direction'] ?? 'all') !== 'all' && $filters['direction'] !== $ledger['direction']) {
            $query->whereRaw('1 = 0');
        }
        if (($search = $filters['search'] ?? '') !== '') {
            $literal = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);
            $query->whereRaw("h.source_number LIKE ? ESCAPE '!'", ['%'.$literal.'%']);
        }
        if (! empty($filters['from'])) {
            $query->where('h.'.$ledger['time'], '>=', $filters['from']);
        }
        if (! empty($filters['until'])) {
            $query->where('h.'.$ledger['time'], '<', $filters['until']);
        }
        if (! empty($filters['account_id']) || ! empty($filters['method_id'])) {
            $query->whereExists(function (Builder $q) use ($ledger, $filters): void {
                $q->selectRaw('1')->from($ledger['lines'].' as l')->whereColumn('l.'.$ledger['foreign'], 'h.id');
                foreach (['account_id', 'method_id'] as $key) {
                    if (! empty($filters[$key])) {
                        $q->where('l.'.$key, $filters[$key]);
                    }
                }
            });
        }

        return $query;
    }

    private function header(array $row): array
    {
        $row['total'] = (string) BigDecimal::of((string) $row['total'])->toScale(in_array($row['source_type'], ['purchase', 'purchase_return'], true) ? 6 : 2);
        $row['occurred_at'] = Carbon::parse($row['occurred_at'], config('app.timezone'))->utc()->toISOString();

        return $row;
    }

    public function paginate(int $page, array $filters): array
    {
        $union = $this->query('incoming', $filters)->unionAll($this->query('outgoing', $filters));
        if ($this->refundsAvailable()) {
            $union->unionAll($this->query('refund', $filters));
        }
        if ($this->supplierReceiptsAvailable()) {
            $union->unionAll($this->query('supplier-refund', $filters));
        }
        $result = DB::query()->fromSub($union, 'history')->orderByDesc('occurred_at')->orderBy('direction')->orderByDesc('id')->orderBy('source_type')->paginate(25, page: $page);

        return ['data' => $result->getCollection()->map(fn ($row) => $this->header((array) $row))->all(),
            'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => 25, 'total' => $result->total()]];
    }

    public function detail(string $direction, string $id): array
    {
        abort_if(! isset(self::LEDGERS[$direction]) || ($direction === 'refund' && ! $this->refundsAvailable())
            || ($direction === 'supplier-refund' && ! $this->supplierReceiptsAvailable()), 404);
        $ledger = self::LEDGERS[$direction];
        $query = $this->query($direction, [])->where('h.id', $id);
        if ($direction === 'incoming') {
            $query->addSelect('h.tendered', 'h.change');
        }
        $row = $query->first();
        abort_if($row === null, 404);
        $result = $this->header((array) $row);
        foreach (['tendered', 'change'] as $key) {
            $result[$key] = $direction === 'incoming' ? (string) BigDecimal::of((string) $result[$key])->toScale(2) : null;
        }
        $columns = ['method_id', 'method_code', 'method_name', 'method_type', 'account_id', 'account_code', 'account_name', 'account_type'];
        $amounts = $direction === 'incoming' ? ['applied_amount', 'tendered_amount', 'change_amount'] : ['amount'];
        $result['payments'] = DB::table($ledger['lines'])->where($ledger['foreign'], $id)->orderBy('line_number')->get([...$columns, ...$amounts])->map(function ($line) use ($direction, $ledger): array {
            $line = (array) $line;
            foreach ($direction === 'incoming' ? ['applied_amount', 'tendered_amount', 'change_amount'] : ['amount'] as $key) {
                $line[$key] = (string) BigDecimal::of((string) $line[$key])->toScale($ledger['scale']);
            }
            if ($direction === 'incoming') {
                $line['amount'] = $line['applied_amount'];
                unset($line['applied_amount']);
            }

            return $line;
        })->all();

        return $result;
    }

    public function options(): array
    {
        $result = [];
        foreach (['account' => 'accounts', 'method' => 'methods'] as $prefix => $key) {
            $columns = [$prefix.'_id as id', $prefix.'_name as name'];
            $union = DB::table('payment_records')->select($columns)->unionAll(DB::table('payment_disbursement_lines')->select($columns));
            if ($this->refundsAvailable()) {
                $union->unionAll(DB::table('payment_refund_lines')->select($columns));
            }
            if ($this->supplierReceiptsAvailable()) {
                $union->unionAll(DB::table('purchase_refund_receipt_lines')->select($columns));
            }
            $result[$key] = DB::query()->fromSub($union, 'snapshots')->select('id')->selectRaw('MAX(name) as name')->groupBy('id')->orderBy('name')->orderBy('id')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $result;
    }

    private function refundsAvailable(): bool
    {
        return Schema::hasTable('payment_refunds') && Schema::hasTable('payment_refund_lines');
    }

    private function supplierReceiptsAvailable(): bool
    {
        return Schema::hasTable('purchase_refund_receipts') && Schema::hasTable('purchase_refund_receipt_lines');
    }
}
