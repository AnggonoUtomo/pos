<?php

namespace App\Modules\Purchasing\PurchaseOrders\Database\Seeders;

use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Models\PurchaseOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class PurchaseOrdersReceiptDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo penerimaan hanya local/testing.');
        }
        DB::transaction(function (): void {
            app(PurchaseOrdersDemoSeeder::class)->run();
            $order = PurchaseOrder::withTrashed()->where('demo_key', 'cctv-electrical-purchase-v1')->lockForUpdate()->firstOrFail();
            if ($order->trashed() || $order->status !== 'draft' || $order->version !== 1 || $order->notes !== 'Draft demo CCTV dan kelistrikan') {
                return;
            }
            app(PostPurchaseOrder::class)->handle($order->id, $order->version);
        }, 3);
    }
}
