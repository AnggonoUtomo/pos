<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Repositories;

use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use App\Modules\Sales\POS\Application\Contracts\InvoiceHistoryReader;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DatabaseInvoiceHistoryReader implements InvoiceHistoryReader
{
    public function __construct(private TablePagination $pagination) {}

    private function scoped(?int $actorId): Builder
    {
        return DB::table('sales_invoices')->when($actorId !== null, fn ($q) => $q->where('actor_id', $actorId));
    }

    public function paginate(int $page, array $filters, ?int $actorId): array
    {
        $query = $this->scoped($actorId);
        if ($filters['search'] !== '') {
            $literal = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtoupper($filters['search']));
            $query->whereRaw("number LIKE ? ESCAPE '!'", ['%'.$literal.'%']);
        }
        foreach (['warehouse_id', 'customer_id'] as $key) {
            if ($filters[$key] !== null) {
                $query->where($key, $filters[$key]);
            }
        }
        if ($filters['from'] !== null) {
            $query->where('posted_at', '>=', $filters['from']);
        }
        if ($filters['until'] !== null) {
            $query->where('posted_at', '<', $filters['until']);
        }
        $page = $query->orderByDesc('posted_at')->orderByDesc('id')->paginate($this->pagination->perPage(),
            ['id', 'number', 'warehouse_name', 'customer_name', 'customer_level_name', 'total', 'posted_at'], 'page', $page);

        return ['data' => $page->getCollection()->map(function ($row): array {
            $row = (array) $row;
            $row['total'] = (string) BigDecimal::of((string) $row['total'])->toScale(2);
            $row['posted_at'] = Carbon::parse($row['posted_at'], config('app.timezone'))->utc()->toISOString();

            return $row;
        })->all(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(), 'total' => $page->total()]];
    }

    public function options(?int $actorId): array
    {
        $result = [];
        foreach (['warehouse' => 'warehouseOptions', 'customer' => 'customerOptions'] as $prefix => $key) {
            $result[$key] = $this->scoped($actorId)->select($prefix.'_id as id')
                ->selectRaw('MAX('.$prefix.'_name) as name')->groupBy($prefix.'_id')->orderBy('name')->orderBy('id')
                ->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $result;
    }
}
