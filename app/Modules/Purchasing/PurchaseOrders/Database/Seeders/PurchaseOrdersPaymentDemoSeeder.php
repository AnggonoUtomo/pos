<?php

namespace App\Modules\Purchasing\PurchaseOrders\Database\Seeders;

use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentMethods;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Models\PurchaseOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class PurchaseOrdersPaymentDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo pembayaran hanya local/testing.');
        }
        DB::transaction(function (): void {
            app(PurchaseOrdersReceiptDemoSeeder::class)->run();
            $order = PurchaseOrder::withTrashed()->where('demo_key', 'cctv-electrical-purchase-v1')->lockForUpdate()->firstOrFail();
            if ($order->trashed() || $order->status !== 'posted' || $order->version !== 2 || $order->posted_from_version !== 1 || $order->payment_disbursement_id !== null || $order->notes !== 'Draft demo CCTV dan kelistrikan') {
                return;
            }
            $method = app(PurchasePaymentMethods::class)->choices()[0] ?? throw new LogicException('Demo memerlukan metode pembayaran aktif.');
            app(PayPurchaseOrder::class)->handle($order->id, 2, [['method_id' => $method['id'], 'amount' => $order->total_value]]);
        }, 3);
    }
}
