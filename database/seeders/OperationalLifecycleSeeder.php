<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Actions\ManageCashbook;
use App\Modules\Finance\Payments\Application\Contracts\CashDrawerPosition;
use App\Modules\Finance\Payments\Database\Seeders\LifecyclePaymentsSeeder;
use App\Modules\Inventory\Items\Database\Seeders\LifecycleItemsSeeder;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostAdjustment;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Transfers\Application\Actions\ManageTransferDraft;
use App\Modules\Inventory\Transfers\Application\Actions\PostTransfer;
use App\Modules\Inventory\Units\Database\Seeders\LifecycleUnitsSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\LifecycleWarehousesSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ReceiveAndPayPurchase;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\PostPurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\SettleReturnRefund as SettleSupplierRefund;
use App\Modules\Purchasing\Returns\Application\Queries\QuoteReturnRefund as QuoteSupplierRefund;
use App\Modules\Purchasing\Suppliers\Database\Seeders\LifecycleSuppliersSeeder;
use App\Modules\Reporting\OperationalReports\Application\Queries\OperationalReport;
use App\Modules\Sales\Parties\Database\Seeders\LifecyclePartiesSeeder;
use App\Modules\Sales\POS\Application\Actions\ManageCashierShift;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Application\Actions\PostReturnDraft;
use App\Modules\Sales\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Sales\Returns\Application\Queries\QuoteReturnRefund;
use Brick\Math\BigDecimal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Spatie\Activitylog\Models\Activity;

class OperationalLifecycleSeeder extends Seeder
{
    public const DATASET = 'cctv-electrical-lifecycle-v2';

    public const BUSINESS_TABLES = [
        'customer_levels', 'customers', 'fifo_consumptions', 'fifo_layers', 'item_prices', 'item_units', 'items',
        'payment_accounts', 'payment_disbursement_lines', 'payment_disbursements', 'payment_methods',
        'payment_receipts', 'payment_records', 'payment_refund_lines', 'payment_refunds', 'pos_held_carts',
        'purchase_order_lines', 'purchase_orders', 'purchase_refund_receipt_lines', 'purchase_refund_receipts',
        'purchase_return_lines', 'purchase_return_refund_allocations', 'purchase_return_refund_scopes',
        'purchase_return_settlements', 'purchase_returns', 'sales_invoice_lines', 'sales_invoices',
        'sales_return_lines', 'sales_return_refund_allocations', 'sales_return_refund_scopes',
        'sales_return_settlements', 'sales_returns', 'stock_adjustments', 'stock_balances', 'stock_issue_lines',
        'stock_issues', 'stock_movements', 'stock_opening_lines', 'stock_openings', 'stock_receipt_lines',
        'stock_receipts', 'stock_return_allocations', 'stock_transfer_lines', 'stock_transfers',
        'suppliers', 'units', 'warehouses',
    ];

    public static function marker(): ?Activity
    {
        return Activity::query()->where('event', 'training.lifecycle.seeded')->where('properties->dataset', self::DATASET)->first();
    }

    public function run(?int $actorId = null, bool $allowProduction = false): array
    {
        if (! app()->environment(['local', 'testing']) && ! ($allowProduction && PHP_SAPI === 'cli')) {
            throw new LogicException('Dataset latihan production hanya melalui penggantian satu kali yang disetujui.');
        }
        $actor = User::query()->where('is_active', true)->when($actorId !== null, fn ($q) => $q->whereKey($actorId))
            ->whereHas('roles', fn ($q) => $q->where('name', 'super-system')->where('guard_name', 'web'))->orderBy('id')->firstOrFail();
        $previous = Auth::user();
        Auth::setUser($actor);
        try {
            return DB::transaction(function () use ($actor): array {
                User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
                if ($marker = self::marker()) {
                    return $marker->properties['result'];
                }
                foreach (self::BUSINESS_TABLES as $table) {
                    if (DB::table($table)->exists()) {
                        throw new LogicException('Seeder tidak mereset/menimpa data existing: '.$table);
                    }
                }
                foreach (['cashbook_openings', 'cashbook_entries', 'cashier_shifts'] as $table) {
                    if (DB::table($table)->exists()) {
                        throw new LogicException('Seeder tidak menimpa buku kas atau shift existing: '.$table);
                    }
                }
                $context = ['actor_id' => $actor->id];
                foreach ([LifecycleUnitsSeeder::class, LifecycleWarehousesSeeder::class, LifecyclePartiesSeeder::class,
                    LifecycleItemsSeeder::class, LifecycleSuppliersSeeder::class, LifecyclePaymentsSeeder::class] as $seeder) {
                    $context = app($seeder)->run($context);
                }
                $result = $this->transactions($context);
                activity('training')->causedBy($actor)->event('training.lifecycle.seeded')
                    ->withProperties(['dataset' => self::DATASET, 'result' => $result])->log('Operational learning dataset created');

                return $result;
            }, 3);
        } finally {
            $previous ? Auth::setUser($previous) : Auth::forgetUser();
        }
    }

    private function transactions(array $c): array
    {
        $actor = $c['actor_id'];
        $warehouse = $c['warehouses']['LAT-UTAMA'];
        $destination = $c['warehouses']['LAT-CADANGAN'];
        $cash = $c['methods']['TUNAI'];
        $transfer = $c['methods']['TRANSFER'];
        $openingLines = [];
        foreach ([['NVR', 'BOX', '2', '2000000'], ['UTP', 'ROLL', '1', '915000'], ['NYM', 'ROLL', '1', '1000000'],
            ['MCB', 'BOX', '2', '360000'], ['RJ45', 'PACK', '2', '100000']] as [$item, $unit, $quantity, $cost]) {
            $openingLines[] = ['item_id' => $c['items'][$item], 'unit_id' => $c['units'][$unit], 'quantity' => $quantity, 'unit_cost' => $cost];
        }
        $opening = app(ManageOpening::class)->create(['warehouse_id' => $warehouse, 'notes' => 'LATIHAN 01: stok fisik awal, bukan pembelian', 'lines' => $openingLines]);
        app(PostOpening::class)->handle($opening);
        $purchase = app(ManagePurchaseOrder::class)->save(['supplier_id' => $c['suppliers']['CCTV'], 'warehouse_id' => $warehouse,
            'notes' => 'LATIHAN 02: 2 dus kamera, terima penuh dan bayar lunas',
            'lines' => [['item_id' => $c['items']['KAMERA'], 'unit_id' => $c['units']['BOX'], 'quantity' => '2', 'unit_cost' => '1200000']]]);
        app(ReceiveAndPayPurchase::class)->handle($purchase, 1, [['method_id' => $transfer, 'amount' => '2400000']], $actor);
        $drawer = DB::table('payment_methods')->where('id', $cash)->value('payment_account_id');
        app(ManageCashbook::class)->open(['account_id' => $drawer, 'amount' => '500000', 'starts_on' => now('Asia/Jakarta')->toDateString()], $actor);
        $shift = strtolower((string) Str::ulid());
        app(ManageCashierShift::class)->open($shift, $drawer, $actor);
        $invoices = [];
        foreach ([
            ['INSTALLER', 'KAMERA', 'UNIT', '2', [], [['method_id' => $transfer, 'amount' => '600000'], ['method_id' => $cash, 'amount' => '350000']]],
            ['ECERAN', 'NYM', 'M', '2.5', ['discount_type' => 'fixed', 'discount_value' => '2500', 'tax_rate' => '11'], [['method_id' => $cash, 'amount' => '40000']]],
            ['GROSIR', 'RJ45', 'PACK', '1', ['discount_type' => 'percent', 'discount_value' => '10', 'tax_rate' => '11'], [['method_id' => $transfer, 'amount' => '199800']]],
        ] as [$level, $item, $unit, $quantity, $pricing, $payments]) {
            $lines = [['item_id' => $c['items'][$item], 'unit_id' => $c['units'][$unit], 'quantity' => $quantity]];
            $quote = app(CheckCart::class)->handle($warehouse, $c['customers'][$level], [new CartLineData($lines[0]['item_id'], $lines[0]['unit_id'], $quantity)], pricing: $pricing);
            $invoices[] = app(PostInvoice::class)->handle(['idempotency_key' => strtolower((string) Str::ulid()),
                'warehouse_id' => $warehouse, 'customer_id' => $c['customers'][$level], 'quote_fingerprint' => $quote['fingerprint'],
                'pricing' => $pricing, 'lines' => $lines, 'payments' => $payments], $actor);
        }
        $returnInput = ['invoice_id' => $invoices[0]['id'], 'notes' => 'LATIHAN 03: kamera layak jual dikembalikan 1 unit',
            'lines' => [['invoice_line_id' => $invoices[0]['lines'][0]['id'], 'quantity' => '1']]];
        $salesReturn = app(ManageReturnDraft::class)->save($returnInput);
        app(PostReturnDraft::class)->handle($salesReturn, 1, $actor);
        $quote = app(QuoteReturnRefund::class)->handle($salesReturn);
        app(SettleReturnRefund::class)->handle($salesReturn, ['version' => $quote['version'], 'quote_fingerprint' => $quote['quote_fingerprint'],
            'payments' => [['method_id' => $cash, 'amount' => $quote['total']]]], $actor);
        $balance = app(CashDrawerPosition::class)->read($drawer)->balance;
        app(ManageCashierShift::class)->close($shift, $balance, '', $actor);
        $purchaseReturnInput = ['purchase_order_id' => $purchase, 'notes' => 'LATIHAN 04: kembali ke supplier 0,25 dus = 1 unit',
            'lines' => [['purchase_order_line_id' => DB::table('purchase_order_lines')->where('purchase_order_id', $purchase)->value('id'), 'quantity' => '0.25']]];
        $purchaseReturn = app(ManagePurchaseReturnDraft::class)->save($purchaseReturnInput);
        app(PostPurchaseReturnDraft::class)->handle($purchaseReturn, 1, $actor);
        $quote = app(QuoteSupplierRefund::class)->handle($purchaseReturn);
        app(SettleSupplierRefund::class)->handle($purchaseReturn, ['version' => $quote['version'], 'quote_fingerprint' => $quote['quote_fingerprint'],
            'payments' => [['method_id' => $transfer, 'amount' => $quote['total']]]], $actor);
        $move = app(ManageTransferDraft::class)->save(['source_warehouse_id' => $warehouse, 'destination_warehouse_id' => $destination,
            'notes' => 'LATIHAN 05: pindahkan 2 kamera ke cadangan',
            'lines' => [['item_id' => $c['items']['KAMERA'], 'unit_id' => $c['units']['UNIT'], 'quantity' => '2']]]);
        app(PostTransfer::class)->handle($move, ['version' => 1], $actor);
        $adjustment = app(PostAdjustment::class)->handle(['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $warehouse,
            'item_id' => $c['items']['KAMERA'], 'direction' => 'out', 'quantity' => '1', 'reason' => 'LATIHAN 06: selisih fisik disahkan 1 kamera'], $actor);
        $drafts = [
            'purchase' => app(ManagePurchaseOrder::class)->save(['supplier_id' => $c['suppliers']['LISTRIK'], 'warehouse_id' => $warehouse,
                'notes' => 'LATIHAN DRAFT: belum menerima stok atau membayar',
                'lines' => [['item_id' => $c['items']['MCB'], 'unit_id' => $c['units']['BOX'], 'quantity' => '1', 'unit_cost' => '360000']]]),
            'sales_return' => app(ManageReturnDraft::class)->save(array_replace($returnInput, ['notes' => 'LATIHAN DRAFT: belum menerima barang atau refund'])),
            'purchase_return' => app(ManagePurchaseReturnDraft::class)->save(array_replace($purchaseReturnInput, ['notes' => 'LATIHAN DRAFT: belum menyerahkan barang atau menerima uang'])),
            'transfer' => app(ManageTransferDraft::class)->save(['source_warehouse_id' => $warehouse, 'destination_warehouse_id' => $destination,
                'notes' => 'LATIHAN DRAFT: belum memindahkan 50 meter UTP',
                'lines' => [['item_id' => $c['items']['UTP'], 'unit_id' => $c['units']['M'], 'quantity' => '50']]]),
        ];
        $report = app(OperationalReport::class)->handle(['from' => now('Asia/Jakarta')->toDateString(), 'to' => now('Asia/Jakarta')->toDateString()]);
        foreach (['sales' => ['total', '1138650'], 'purchases' => ['total_value', '2400000'], 'sales_returns' => ['settled_total', '450000'],
            'purchase_returns' => ['settled_total', '300000'], 'stock' => ['total_value', '8210000'], 'payments' => ['net_movement', '-1411350']] as $section => [$key, $value]) {
            if (! BigDecimal::of($report[$section][$key])->isEqualTo($value)) {
                throw new LogicException('Rekonsiliasi lifecycle gagal: '.$section);
            }
        }

        return ['dataset' => self::DATASET, 'opening' => $opening, 'purchase' => $purchase,
            'invoices' => array_column($invoices, 'id'), 'sales_return' => $salesReturn, 'purchase_return' => $purchaseReturn,
            'transfer' => $move, 'adjustment' => $adjustment, 'drafts' => $drafts,
            'expected' => ['camera_main' => '3', 'camera_secondary' => '2', 'stock_value' => '8210000', 'net_cash_movement' => '-1411350']];
    }
}
