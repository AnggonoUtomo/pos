<?php

namespace Tests\Fixtures;

use App\Modules\Finance\Payments\Application\Actions\RecordSalePayment;
use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\PurchasePaymentRequest;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PaymentHistoryFixture
{
    public static function seed(): array
    {
        app(PaymentsDemoSeeder::class)->run();
        $methods = DB::table('payment_methods')->pluck('id', 'code')->all();
        $sale = app(RecordSalePayment::class)->handle(['source_id' => strtolower((string) Str::ulid()), 'source_number' => 'POS-QA!%_A', 'total' => '10000.12',
            'payments' => [['method_id' => $methods['TUNAI'], 'amount' => '5000.12'], ['method_id' => $methods['TRANSFER'], 'amount' => '6000']]]);
        $purchase = DB::transaction(fn () => app(PurchasePaymentWriter::class)->pay(new PurchasePaymentRequest(strtolower((string) Str::ulid()), 'PO-CCTV-QA', '123.123456', [['method_id' => $methods['TRANSFER'], 'amount' => '123.123456']])));
        $next = app(RecordSalePayment::class)->handle(['source_id' => strtolower((string) Str::ulid()), 'source_number' => 'POS-NEXT-DAY', 'total' => '400', 'payments' => [['method_id' => $methods['TUNAI'], 'amount' => '400']]]);
        DB::table('payment_receipts')->where('id', $sale['id'])->update(['posted_at' => '2026-10-15 17:00:00']);
        DB::table('payment_disbursements')->where('id', $purchase->id)->update(['paid_at' => '2026-10-16 16:59:59']);
        DB::table('payment_receipts')->where('id', $next['id'])->update(['posted_at' => '2026-10-16 17:00:00']);

        return ['incoming' => $sale['id'], 'outgoing' => $purchase->id, 'next' => $next['id'], 'methods' => $methods,
            'cash_account' => DB::table('payment_methods')->where('id', $methods['TUNAI'])->value('payment_account_id'),
            'bank_account' => DB::table('payment_methods')->where('id', $methods['TRANSFER'])->value('payment_account_id')];
    }

    public static function fingerprint(): string
    {
        return hash('sha256', json_encode(array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->all(),
            ['payment_accounts', 'payment_methods', 'payment_receipts', 'payment_records', 'payment_disbursements', 'payment_disbursement_lines', 'activity_log', 'stock_balances', 'stock_movements', 'fifo_layers']), JSON_THROW_ON_ERROR));
    }
}
