<?php

namespace App\Modules\Reporting\OperationalReports\Application\Queries;

use App\Modules\Sales\POS\Application\Contracts\InvoiceHistoryReader;

class DashboardSummary
{
    public function __construct(private OperationalReport $report, private InvoiceHistoryReader $invoices) {}

    public function handle(bool $viewReports, bool $viewInvoices, ?int $invoiceActorId): array
    {
        $summary = null;
        if ($viewReports) {
            $report = $this->report->handle([]);
            $summary = [
                'date' => $report['filters']['from'],
                'sales' => ['count' => $report['sales']['count'], 'total' => $report['sales']['total']],
                'purchases' => $report['purchases'],
                'sales_returns' => $report['sales_returns'],
                'purchase_returns' => $report['purchase_returns'],
                'payments' => $report['payments'],
                'stock' => ['count' => $report['stock']['count'], 'total_value' => $report['stock']['total_value']],
            ];
        }
        $recent = null;
        if ($viewInvoices) {
            $records = $this->invoices->paginate(1, ['search' => '', 'warehouse_id' => null, 'customer_id' => null,
                'from' => null, 'until' => null], $invoiceActorId);
            $recent = array_slice($records['data'], 0, 5);
        }

        return ['summary' => $summary, 'recent_sales' => $recent,
            'sales_scope' => $viewInvoices ? ($invoiceActorId === null ? 'all' : 'own') : null,
            'generated_at' => now()->toIso8601String()];
    }
}
