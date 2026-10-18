<?php

namespace App\Modules\Sales\Returns\Database\Seeders;

use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Application\Contracts\ReturnDraftRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReturnsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo retur hanya local/testing.');
        }
        DB::transaction(function (): void {
            $key = 'cctv-electrical-return-v1';
            if (app(ReturnDraftRepository::class)->demoExists($key)) {
                return;
            }
            $lookup = app(ReturnInvoiceLookup::class);
            foreach ($lookup->search('') as $choice) {
                $invoice = $lookup->find($choice['id']);
                if ($invoice === null || ! str_starts_with($invoice->header['customer_code'], 'DEMO-')) {
                    continue;
                }
                $lines = [];
                foreach ($invoice->lines as $line) {
                    if (str_starts_with($line['sku'], 'DEMO-CCTV-') || str_starts_with($line['sku'], 'DEMO-ELEC-')) {
                        $lines[] = ['invoice_line_id' => $line['id'], 'quantity' => $line['quantity']];
                    }
                }
                if ($lines !== []) {
                    app(ManageReturnDraft::class)->save(['invoice_id' => $invoice->header['id'],
                        'notes' => 'Permintaan retur demo CCTV dan kelistrikan', 'lines' => $lines], demoKey: $key);

                    return;
                }
            }
            $this->command?->info('SKIPPED: belum ada invoice demo CCTV/kelistrikan pada 25 invoice terbaru.');
        }, 3);
    }
}
