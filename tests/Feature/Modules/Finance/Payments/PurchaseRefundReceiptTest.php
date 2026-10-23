<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptWriter;
use App\Modules\Finance\Payments\Application\DTOs\PurchaseRefundReceiptRequest;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchaseRefundReceiptTest extends StockTestCase
{
    private function request(string $total = '1.000001'): PurchaseRefundReceiptRequest
    {
        $this->seed(PaymentsDemoSeeder::class);
        $supplier = Supplier::create(['code' => 'FIN-REFUND', 'name' => 'Supplier', 'is_active' => true]);
        $purchase = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $supplier->id]);
        app(PostPurchaseOrder::class)->handle($purchase, 1);
        $methods = DB::table('payment_methods')->pluck('id', 'code');
        app(PayPurchaseOrder::class)->handle($purchase, 2, [['method_id' => $methods['TUNAI'], 'amount' => '240000']]);

        return new PurchaseRefundReceiptRequest(strtolower((string) Str::ulid()), 'PR-TEST', $purchase,
            DB::table('purchase_orders')->where('id', $purchase)->value('payment_disbursement_id'), $total,
            [['method_id' => $methods['TRANSFER'], 'amount' => $total]], $this->manager->id);
    }

    public function test_writer_preserves_six_decimals_replays_and_guards_referenced_masters(): void
    {
        $request = $this->request();
        $writer = app(PurchaseRefundReceiptWriter::class);
        $result = DB::transaction(fn () => $writer->receive($request));
        $this->assertSame('1.000001', $result->snapshot['total']);
        $this->assertTrue(DB::transaction(fn () => app(PaymentsRepository::class)->hasMethodHistory($request->payments[0]['method_id'])));
        $this->assertSame($result->id, DB::transaction(fn () => $writer->receive($request))->id);
        $this->assertDatabaseCount('purchase_refund_receipts', 1);
    }

    public function test_writer_requires_caller_transaction(): void
    {
        $request = $this->request();
        // RefreshDatabase owns a wrapping transaction; pretend there is none without committing fixture data.
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $this->expectException(LogicException::class);
        app(PurchaseRefundReceiptWriter::class)->receive($request);
    }

    public function test_writer_rejects_mismatched_paid_proof(): void
    {
        $request = $this->request();
        $bad = new PurchaseRefundReceiptRequest($request->sourceId, $request->sourceNumber, $request->purchaseId,
            strtolower((string) Str::ulid()), $request->total, $request->payments);
        try {
            DB::transaction(fn () => app(PurchaseRefundReceiptWriter::class)->receive($bad));
            $this->fail('Bukti lunas harus cocok.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('purchase_refund_receipts', 0);
        }
    }
}
