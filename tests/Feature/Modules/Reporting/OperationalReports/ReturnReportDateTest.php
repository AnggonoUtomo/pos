<?php

namespace Tests\Feature\Modules\Reporting\OperationalReports;

use App\Modules\Purchasing\Returns\Application\Contracts\PurchaseReturnReportReader;
use App\Modules\Sales\Returns\Application\Contracts\SalesReturnReportReader;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\PurchaseReturnRefundFixture;
use Tests\Fixtures\ReturnRefundFixture;
use Tests\TestCase;

class ReturnReportDateTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('dates')]
    public function test_real_return_postings_use_inclusive_wib_days_without_changing_data(string $timezone, string $instant, int $expected): void
    {
        $previous = date_default_timezone_get();
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        try {
            $this->travelTo(Carbon::parse($instant));
            $invoice = ReturnRefundFixture::invoice();
            ReturnRefundFixture::posted($invoice, '0.5');
            PurchaseReturnRefundFixture::posted(PurchaseReturnRefundFixture::purchase(), '0.5');
            $tables = ['sales_returns', 'purchase_returns', 'stock_receipts', 'stock_issues', 'stock_movements', 'activity_log'];
            $fingerprint = fn () => array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), $tables);
            $before = $fingerprint();
            $from = Carbon::parse('2026-11-01', 'Asia/Jakarta')->setTimezone($timezone)->toDateTimeString();
            $until = Carbon::parse('2026-11-02', 'Asia/Jakarta')->setTimezone($timezone)->toDateTimeString();
            foreach ([SalesReturnReportReader::class, PurchaseReturnReportReader::class] as $reader) {
                $this->assertSame($expected, app($reader)->summary($from, $until, null)['count']);
                $this->assertSame(0, app($reader)->summary($from, $until, strtolower((string) str()->ulid()))['count']);
            }
            $this->assertSame($before, $fingerprint());
        } finally {
            $this->travelBack();
            date_default_timezone_set($previous);
        }
    }

    public static function dates(): iterable
    {
        foreach (['UTC', 'Asia/Jakarta'] as $timezone) {
            foreach (['2026-10-31T16:59:59Z' => 0, '2026-10-31T17:00:00Z' => 1,
                '2026-11-01T16:59:59Z' => 1, '2026-11-01T17:00:00Z' => 0] as $instant => $expected) {
                yield [$timezone, $instant, $expected];
            }
        }
    }
}
