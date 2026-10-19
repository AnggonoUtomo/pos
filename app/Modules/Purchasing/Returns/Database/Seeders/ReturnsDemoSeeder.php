<?php

namespace App\Modules\Purchasing\Returns\Database\Seeders;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnDraftRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReturnsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo retur pembelian hanya boleh dijalankan pada local/testing.');
        }

        DB::transaction(function (): void {
            $key = 'cctv-electrical-return-v1';
            if (app(ReturnDraftRepository::class)->demoExists($key)) {
                return;
            }

            $purchase = collect(app(ReturnPurchaseLookup::class)->searchEligible('DEMO-SUP-CCTV'))
                ->first(fn ($purchase) => $purchase->header['supplier_code'] === 'DEMO-SUP-CCTV'
                    && collect($purchase->lines)->contains(fn (array $line) => in_array($line['sku'], ['DEMO-CCTV-IP-2MP', 'DEMO-ELEC-NYM-3X15'], true)));
            if ($purchase === null) {
                return;
            }

            $lines = collect($purchase->lines)
                ->filter(fn (array $line) => in_array($line['sku'], ['DEMO-CCTV-IP-2MP', 'DEMO-ELEC-NYM-3X15'], true))
                ->map(fn (array $line) => ['purchase_order_line_id' => $line['id'], 'quantity' => $line['quantity']])
                ->values()->all();
            if ($lines === []) {
                return;
            }

            app(ManagePurchaseReturnDraft::class)->save([
                'purchase_order_id' => $purchase->header['id'],
                'notes' => 'Draft demo retur CCTV',
                'lines' => $lines,
            ], demoKey: $key);
        }, 3);
    }
}
