<?php

namespace App\Modules\Reporting\OperationalReports\Application\Queries;

use App\Modules\Finance\Payments\Application\Contracts\PaymentHistoryReader;
use App\Modules\Inventory\Stock\Application\Contracts\StockReportReader;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseReportReader;
use App\Modules\Purchasing\Returns\Application\Contracts\PurchaseReturnReportReader;
use App\Modules\Sales\POS\Application\Contracts\SalesReportReader;
use App\Modules\Sales\Returns\Application\Contracts\SalesReturnReportReader;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OperationalReport
{
    public function __construct(private SalesReportReader $sales, private PurchaseReportReader $purchases,
        private SalesReturnReportReader $saleReturns, private PurchaseReturnReportReader $purchaseReturns,
        private StockReportReader $stock, private PaymentHistoryReader $payments, private WarehouseLookup $warehouses,
        private TablePagination $pagination) {}

    public function handle(array $input): array
    {
        $filters = Validator::make($input + ['from' => now('Asia/Jakarta')->toDateString(), 'to' => now('Asia/Jakarta')->toDateString()], [
            'from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'warehouse_id' => ['nullable', 'ulid'], 'page' => ['sometimes', 'integer', 'min:1', 'max:2147483647']])->validate();
        $perPage = $this->pagination->perPage();
        $from = Carbon::parse($filters['from'], 'Asia/Jakarta')->startOfDay();
        $until = Carbon::parse($filters['to'], 'Asia/Jakarta')->addDay()->startOfDay();
        if ($from->diffInDays($until) > 366) {
            throw ValidationException::withMessages(['to' => 'Periode maksimal 366 hari.']);
        }
        $id = ! empty($filters['warehouse_id']) ? strtolower($filters['warehouse_id']) : null;
        $filters['warehouse_id'] = $id ?? '';
        $from->setTimezone(config('app.timezone'));
        $until->setTimezone(config('app.timezone'));

        return DB::transaction(fn () => ['filters' => $filters, 'warehouses' => $this->warehouses->choices(),
            'sales' => $this->sales->summary($from->toDateTimeString(), $until->toDateTimeString(), $id),
            'purchases' => $this->purchases->summary($from->toDateTimeString(), $until->toDateTimeString(), $id),
            'sales_returns' => $this->saleReturns->summary($from->toDateTimeString(), $until->toDateTimeString(), $id),
            'purchase_returns' => $this->purchaseReturns->summary($from->toDateTimeString(), $until->toDateTimeString(), $id),
            'stock' => $this->stock->summary($id, (int) ($filters['page'] ?? 1), $perPage), 'payments' => $this->payments->summary($from->toDateTimeString(), $until->toDateTimeString()),
            'generated_at' => now()->toIso8601String()]);
    }
}
