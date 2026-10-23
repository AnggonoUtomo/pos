<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentProofLookup;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchaseRefundBasisTest extends StockTestCase
{
    private function received(): string
    {
        $this->seed(PaymentsDemoSeeder::class);
        $supplier = Supplier::create(['code' => 'REF-BASIS', 'name' => 'Supplier Kamera', 'is_active' => true]);
        $id = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $supplier->id]);
        app(PostPurchaseOrder::class)->handle($id, 1);

        return $id;
    }

    private function pay(string $id): void
    {
        app(PayPurchaseOrder::class)->handle($id, 2, [['method_id' => DB::table('payment_methods')->where('code', 'TUNAI')->value('id'),
            'amount' => '240000']]);
    }

    public function test_only_proven_paid_purchase_is_eligible_with_redacted_immutable_basis(): void
    {
        $id = $this->received();
        $lookup = app(ReturnPurchaseLookup::class);
        $this->assertNull($lookup->refundBasis($id));
        $this->pay($id);
        $basis = $lookup->refundBasis($id);
        $this->assertNotNull($basis);
        $this->assertSame('240000.000000', $basis->header['paid_total']);
        $this->assertSame('2.000000', $basis->lines[0]['quantity']);
        DB::table('payment_methods')->update(['is_active' => false, 'name' => 'Berubah']);
        DB::table('items')->update(['is_active' => false]);
        $this->assertEquals($basis, $lookup->refundBasis($id));
        $proof = app(PurchasePaymentProofLookup::class)->find($id);
        $this->assertSame('240000.000000', $proof->total);
        foreach (['account_id', 'request_hash', 'bank_name', 'paid_by'] as $private) {
            $this->assertStringNotContainsString($private, json_encode($proof, JSON_THROW_ON_ERROR));
        }
    }

    public function test_corrupt_finance_lines_or_purchase_snapshot_fail_closed(): void
    {
        $id = $this->received();
        $this->pay($id);
        $lookup = app(ReturnPurchaseLookup::class);
        $line = DB::table('payment_disbursement_lines')->sole();
        DB::table('payment_disbursement_lines')->where('id', $line->id)->update(['amount' => '1']);
        $this->assertNull($lookup->refundBasis($id));
        DB::table('payment_disbursement_lines')->where('id', $line->id)->update(['amount' => $line->amount]);
        $this->assertNotNull($lookup->refundBasis($id));
        DB::table('purchase_orders')->where('id', $id)->update(['payment_snapshot' => json_encode(['total' => '1', 'payments' => []])]);
        $this->assertNull($lookup->refundBasis($id));
    }
}
